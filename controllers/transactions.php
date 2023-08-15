<?php
namespace packages\financial\controllers;

use packages\userpanel;
use packages\userpanel\{Date, Log, User};
use packages\financial\{views\transactions\pay as PayView, views\transactions as financialViews};
use packages\financial\payport\{AlreadyVerified, GatewayException, Redirect, VerificationException};
use packages\base\{DB, db\duplicateRecord, view\Error, views\FormError, Packages, Http, inputValidation, InputValidationException, NotFound, Options, db\Parenthesis, Response, Utility\Safe};
use packages\financial\{Bank\Account, Authentication, Authorization, Controller, Currency, Events, Logs,
						Transaction, Transaction_product, Transaction_pay, View, Views, Payport, Payport_pay,
						Transactions_products_param, Stats, products\AddingCredit, validators};
use packages\financial\Contracts\ITransactionManager;
use packages\financial\FinancialService;

class Transactions extends Controller
{
	use Transactions\MergeTrait;

	public static function checkBanktransferFollowup(int $bank, string $code) {
		$account = new Account();
		$account->where("bank_id", $bank);
		$accounts = array_column($account->get(null, "id"), "id");
		if (!$accounts) {
			return false;
		}
		$banktransferPays = new transaction_pay();
		db::join("financial_transactions_pays_params params1", "params1.pay=financial_transactions_pays.id", "INNER");
		db::joinWhere("financial_transactions_pays_params params1", "params1.name", "bankaccount");
		db::joinWhere("financial_transactions_pays_params params1", "params1.value", $accounts, "IN");
		db::join("financial_transactions_pays_params params2", "params2.pay=financial_transactions_pays.id", "INNER");
		db::joinWhere("financial_transactions_pays_params params2", "params2.name", "followup");
		db::joinWhere("financial_transactions_pays_params params2", "params2.value", $code);
		return $banktransferPays->has();
	}

	public static function getPay($data): Transaction_pay {
		$check = Authentication::check();
		$isOperator = false;
		$types = array();
		if ($check) {
			$isOperator = Authorization::is_accessed("transactions_anonymous");
			$types = Authorization::childrenTypes();
		}
		$pay = new Transaction_pay();
		$pay->with("currency");
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_pays.transaction", "INNER");
		$parenthesis = new parenthesis();
		if ($check) {
			if ($isOperator) {
				$parenthesis->where("financial_transactions.user", null, "is", "or");
			}
			db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
			if ($types) {
				$parenthesis->where("userpanel_users.type", $types, 'in', "or");
			} else {
				$parenthesis->where("userpanel_users.id", authentication::getID(), "=", "or");
			}
			$pay->where($parenthesis);
		} else if ($token = http::getURIData("token")) {
			$pay->where("financial_transactions.token", $token);
		} else {
			throw new NotFound();
		}
		$pay->where("financial_transactions_pays.id", $data["pay"]);
		$pay = $pay->getOne();
		if(!$pay){
			throw new NotFound;
		}
		return $pay;
	}
	public static function payAcceptor(Transaction_pay $pay) {
		$pay->status = Transaction_pay::accepted;
		$pay->setParam('acceptor', Authentication::getID());
		$pay->setParam('accept_date', date::time());
		$pay->save();
		$transaction = $pay->transaction;
		$log = new Log();
		$log->user = Authentication::getUser();
		$log->type = logs\transactions\pay::class;
		$log->title = t("financial.logs.transaction.pay.accept", ["transaction_id" => $transaction->id, 'pay_id' => $pay->id]);
		$log->parameters = array(
			"pay" => $pay,
			"currency" => $transaction->currency,
		);
		$log->save();
	}
	public static function payRejector(Transaction_pay $pay) {
		$pay->status = Transaction_pay::rejected;
		$log = new log();
		$log->user = Authentication::getUser();
		$log->type = logs\transactions\pay::class;
		$pay->setParam('rejector', Authentication::getID());
		$pay->setParam('reject_date', date::time());
		$pay->save();
		$transaction = $pay->transaction;
		$log->title = t("financial.logs.transaction.pay.reject", ["transaction_id" => $transaction->id, 'pay_id' => $pay->id]);
		$parameters['pay'] = $pay;
		$parameters['currency'] = $transaction->currency;
		$log->parameters = $parameters;
		$log->save();
	}

	public ITransactionManager $transactionManager;
	protected $authentication = true;

	public function __construct(?ITransactionManager $transactionManager = null)
	{
		$this->response = new Response();

		$this->transactionManager = $transactionManager ?? (new FinancialService())->getTransactionManager();

		if (authentication::check()) {
			$this->page = http::getURIData('page');
			$this->items_per_page = http::getURIData('ipp');
			if ($this->page < 1) $this->page = 1;
			if ($this->items_per_page < 1) $this->items_per_page = 25;
			db::pageLimit($this->items_per_page);
			$this->response = new response();
		} else if ($token = http::getURIData("token")) {
			$transaction = new transaction();
			$transaction->where("token", $token);
			if (!$transaction = $transaction->getOne()) {
				parent::response(authentication::FailResponse());
			}
		} else {
			parent::response(authentication::FailResponse());
		}
	}
	public function listtransactions(){
		authorization::haveOrFail('transactions_list');
		$view = view::byName(views\transactions\listview::class);
		$this->response->setView($view);
		$canAccept = Authorization::is_accessed("transactions_pay_accept");
		$exporters = array();
		$exporter = null;
		if ($canAccept) {
			$exporter = new Events\Exporters();
			$exporter->trigger();
			$exporters = $exporter->get();
			$view->setExporters($exporters);
		}
		$types = authorization::childrenTypes();
		$anonymous = authorization::is_accessed("transactions_anonymous");
		$inputsRules = array(
			'id' => array(
				'type' => 'number',
				'optional' => true,
			),
			'title' => array(
				'type' => 'string',
				'optional' =>true,
			),
			'name' => array(
				'type' => 'string',
				'optional' =>true,
			),
			'user' => array(
				'type' => 'number',
				'optional' => true,
			),
			'status' => array(
				'type' => 'number',
				'values' => [Transaction::UNPAID, Transaction::PENDING, Transaction::PAID, Transaction::REFUND, Transaction::EXPIRED],
				'optional' => true,
			),
			'download' => array(
				'type' => 'string',
				'values' => array("csv"),
				'optional' => true,
			),
			'create_from' => array(
				'type' => 'date',
				'unix' => true,
				'optional' => true,
			),
			'create_to' => array(
				'type' => 'date',
				'unix' => true,
				'optional' => true,
			),
			'word' => array(
				'type' => 'string',
				'optional' => true,
			),
			'comparison' => array(
				'type' => 'string',
				'values' => array('equals', 'startswith', 'contains'),
				'default' => 'contains',
				'optional' => true
			)
		);
		if ($canAccept) {
			$inputsRules["download"]["values"] = array_merge($exporter->getExporterNames(), $inputsRules["download"]["values"]);
			$inputsRules['refund'] = array(
				'type' => 'bool',
				'optional' => true,
				'empty' => true,
				'default' => false,
			);
		}
		$searched = false;
		$inputs = $this->checkinputs($inputsRules);
		if (!$canAccept) {
			$inputs["refund"] = false;
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$transaction = new Transaction;
		$transaction->with("currency");
		foreach(array('id', 'title', 'status', 'user') as $item){
			if(isset($inputs[$item])){
				$comparison = $inputs['comparison'];
				if(in_array($item, array('id', 'status', 'user'))){
					$comparison = 'equals';
				}
				$transaction->where("financial_transactions.".$item, $inputs[$item], $comparison);
				$searched = true;
			}
		}
		if (isset($inputs["create_from"])) {
			$transaction->where("financial_transactions.create_at", $inputs["create_from"], ">=");
			$searched = true;
		}
		if (isset($inputs["create_to"])) {
			$transaction->where("financial_transactions.create_at", $inputs["create_to"], "<");
			$searched = true;
		}
		if(isset($inputs['word']) and $inputs['word']){
			$parenthesis = new Parenthesis();
			foreach(array('title') as $item){
				if(!isset($inputs[$item])){
					$parenthesis->orWhere("financial_transactions.{$item}", $inputs['word'], $inputs['comparison']);
				}
			}
			$products = db::subQuery();
			foreach (array('title', 'description') as $item) {
				$products->orWhere("financial_transactions_products.{$item}", $inputs['word'], $inputs['comparison']);
			}
			$parenthesis->orWhere("financial_transactions.id", $products->get("financial_transactions_products", null, "financial_transactions_products.transaction"), "IN");
			$searched = true;
			$transaction->where($parenthesis);
		}

		if($anonymous){
			$transaction->with("user", "LEFT");
			$parenthesis = new Parenthesis();
			$parenthesis->where("userpanel_users.type",  $types, "in");
			$parenthesis->orWhere("financial_transactions.user", null, "is");
			$transaction->where($parenthesis);
		} else {
			$transaction->with("user", "INNER");
			if ($types) {
				$transaction->where("userpanel_users.type", $types, "in");
			} else {
				$transaction->where("userpanel_users.id", authentication::getID());
			}
		}
		if ($inputs["refund"]) {
			$products = db::subQuery();
			$products->where("financial_transactions_products.method", Transaction_product::refund);
			$transaction->where("financial_transactions.id", $products->get("financial_transactions_products", null, "financial_transactions_products.transaction"), "IN");
			$searched = false;
		}
		$transaction->orderBy('financial_transactions.id', 'DESC');
		if (isset($inputs["download"])) {
			$transactions = $transaction->get();
			if (in_array($inputs["download"], $exporter->getExporterNames())) {
				$handler = $exporter->getByName($inputs["download"])->getHandler();
				$responseFile = (new $handler())->export($transactions);
				$this->response->setFile($responseFile);
				$this->response->forceDownload();
			}
		} else {
			if (!$searched) {
				$transaction->where('financial_transactions.status', transaction::expired, '!=');
			}
			$transaction->pageLimit = $this->items_per_page;
			$transactions = $transaction->paginate($this->page);
			$view->setDataList($transactions);
			$view->setPaginate($this->page, db::totalCount(), $this->items_per_page);
		}
		$this->response->setStatus(true);
		return $this->response;
	}
	public function transaction_view($data){
		$transaction = $this->getTransaction($data['id']);
		$view = view::byName("\\packages\\financial\\views\\transactions\\view");
		$view->setTransaction($transaction);
		$this->response->setStatus(true);
		try{
			$currency = $transaction->currency;
			$userCurrency = currency::getDefault($transaction->user);
			if($transaction->status == transaction::unpaid){
				$transaction->currency = currency::getDefault($transaction->user);
				$transaction->price = $transaction->totalPrice();
				$transaction->save();
			}
			$transaction->deleteParam('UnChangableException');
		}catch(currency\UnChangableException $e){
			$this->response->setStatus(false);
			$error = new error();
			$error->setCode('financial.transaction.currency.UnChangableException');
			$view->addError($error);
			$transaction->setParam('UnChangableException', true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	private function getTransaction($id): Transaction {
		$transaction = new transaction();
		$parenthesis = new parenthesis();
		if(authorization::is_accessed("transactions_anonymous")) {
			$parenthesis->where("financial_transactions.user", null, "is", "or");
		}
		if (authentication::check()) {
			$types = authorization::childrenTypes();
			db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
			if ($types) {
				$parenthesis->where("userpanel_users.type", $types, 'in', "or");
			} else {
				$parenthesis->where("userpanel_users.id", authentication::getID(), "=", "or");
			}
			$transaction->where($parenthesis);
		} else if ($token = http::getURIData("token")) {
			$transaction->where("financial_transactions.token", $token);
		} else {
			throw new NotFound();
		}
		if (!$parenthesis->isEmpty()) {
			$transaction->where($parenthesis);
		}
		$transaction->where("financial_transactions.id", $id);
		$transaction = $transaction->getOne("financial_transactions.*");
		if(!$transaction){
			throw new NotFound;
		}
		return $transaction;
	}
	/**
	 * get transaction for pay
	 * also check user permissions for this transaction and can add pay for this transaction
	 *
	 * @see packages/financial/Transaction@canAddPay
	 * @throws packages/base/NotFound if can not find any transaction with t
	 * @return packages/financial/Transaction
	 */
	private function getTransactionForPay($data): Transaction {
		$transaction = $this->getTransaction($data['transaction']);
		if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() < 0 or $transaction->param('UnChangableException')){
			throw new NotFound;
		}
		return $transaction;
	}

	public function pay($data): Response
	{
		$transaction = $this->getTransactionForPay($data);

		$view = View::byName(payView::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);

		$operatorID = null;
		if (Authentication::check()) {
			$currentUserID = Authentication::getID();

			if (!empty(Authorization::childrenTypes()) and $currentUserID !== $transaction->user->id) {
				$operatorID = $currentUserID;
			}
		}

		$paymentMethods = $this->transactionManager->getAvailablePaymentMethods($transaction->id, $operatorID);

		foreach ($paymentMethods as $method) {
			$view->setMethod($method);
		}

		$this->response->setStatus(true);
		return $this->response;
	}

	public function payByCreditView($data): Response
	{
		$transaction = $this->getTransactionForPay($data);
	
		$operator = null;
		if (Authentication::check()) {
			$currentUser = Authentication::getUser();

			if (!empty(Authorization::childrenTypes()) and $currentUser->id !== $transaction->user->id) {
				$operator = $currentUser;
			}
		}

		if (!$this->transactionManager->canPayByCredit($transaction->id, $operator ? $operator->id : null)) {
			throw new NotFound();
		}

		$payer = (($transaction->user->credit <= 0 and $operator) ? $operator : $transaction->user);

		$view = View::byName(payView\credit::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);
		$view->setCredit($payer->credit);
		$view->setCurrency($transaction->currency);
		$view->setDataForm($payer->id, 'user');
		$view->setDataForm(min($transaction->remainPriceForAddPay(), $payer->credit), 'credit');

		$this->response->setStatus(true);

		return $this->response;
	}

	public function payByCredit($data): Response
	{
		$transaction = $this->getTransactionForPay($data);

		$operator = null;
		if (Authentication::check()) {
			$currentUser = Authentication::getUser();

			if (!empty(Authorization::childrenTypes()) and $currentUser->id !== $transaction->user->id) {
				$operator = $currentUser;
			}
		}

		if (!$this->transactionManager->canPayByCredit($transaction->id, $operator ? $operator->id : null)) {
			throw new NotFound();
		}

		$payer = (($transaction->user->credit <= 0 and $operator) ? $operator : $transaction->user);

		$view = View::byName(payView\credit::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);
		$view->setCredit($payer->credit);
		$view->setCurrency($transaction->currency);
		$view->setDataForm($payer->id, 'user');
		$view->setDataForm(min($transaction->remainPriceForAddPay(), $payer->credit), 'credit');

		$rules = array(
			'credit' => array(
				'type' => 'number',
				'min' => 0,
			),
		);

		if ($operator) {
			$rules['user'] = array(
				'type' => User::class,
				'optional' => true,
				'query' => function ($query) use ($transaction, $operator) {
					$query->where("id", [$transaction->user->id, $operator->id], "IN");
				},
			);
		}

		$inputs = $this->checkInputs($rules);

		if (!isset($inputs['user'])) {
			$inputs['user'] = $transaction->user;
		}

		$payerCurrency = Currency::getDefault($inputs['user']);

		if ($payerCurrency->id != $transaction->currency->id) {
			throw new InputValidationException("credit");
		}

		if ($inputs['credit'] > $inputs['user']->credit or $inputs['credit'] > $transaction->remainPriceForAddPay()) {
			throw new InputValidationException('credit');
		}

		$pay = $transaction->addPay(array(
			'method' => transaction_pay::credit,
			'price' => $inputs['credit'],
			"currency" => $transaction->currency->id,
			'params' => array(
				'user' => $inputs['user']->id,
			),
		));

		if ($pay) {
			$inputs['user']->credit -= $inputs['credit'];
			$inputs['user']->save();

			$log = new Log();
			$log->user = Authentication::getID();
			$log->type = logs\transactions\Pay::class;
			$log->title = t("financial.logs.transaction.pay", ["transaction_id" => $transaction->id]);
			$log->parameters = array(
				'pay' => (new Transaction_pay)->byID($pay),
				'currency' => $transaction->currency,
			);
			$log->save();

			$this->response->setStatus(true);
			$redirect = $this->redirectToConfig($transaction);
			$this->response->Go($redirect ? $redirect : userpanel\url("transactions/view/{$transaction->id}"));
		}

		return $this->response;
	}

	public function payByBankTransferView($data): Response
	{
		$transaction = $this->getTransactionForPay($data);

		if (!$this->transactionManager->canPayByTransferBank($transaction->id)) {
			throw new NotFound();
		}

		$view = View::byName(PayView\Banktransfer::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);

		$banktransferPays = (new Transaction_pay())
			->where("transaction", $transaction->id)
			->where("method", Transaction_pay::banktransfer)
			->get();

		$view->setBanktransferPays($banktransferPays);
		$view->setBankAccounts($this->transactionManager->getBankAccountsForTransferPay($transaction->id));

		$this->response->setStatus(true);

		return $this->response;
	}

	public function payByBankTransfer($data): Response
	{
		$transaction = $this->getTransactionForPay($data);

		if (!$this->transactionManager->canPayByTransferBank($transaction->id)) {
			throw new NotFound();
		}

		$view = View::byName(PayView\Banktransfer::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);

		$banktransferPays = (new Transaction_pay())
			->where("transaction", $transaction->id)
			->where("method", Transaction_pay::banktransfer)
			->get();

		$view->setBanktransferPays($banktransferPays);

		$accounts = $this->transactionManager->getBankAccountsForTransferPay($transaction->id);
		$view->setBankAccounts($accounts);

		$rules = array(
			"bankaccount" => array(
				"type" => function ($data, $rule, $input) use ($accounts) {
					foreach ($accounts as $account) {
						if ($account->id == $data) {
							return $account;
						}
					}
					throw new InputValidationException($input);
				},
			),
			"price" => array(
				"type" => "float",
				"zero" => false,
				"min" => 0,
				"max" => $transaction->remainPriceForAddPay(),
			),
			"followup" => array(
				"type" => "string",
			),
			"description" => array(
				"type" => "string",
				"optional" => true,
			),
			"date" => array(
				"type" => "date",
				"unix" => true,
			),
			"attachment" => array(
				"type" => "file",
				"extension" => ["png", "jpeg", "jpg", "gif", "pdf", "csf", "docx"],
				"max-size" => 1024 * 1024 * 5,
				"optional" => true,
				"obj" => true,
			)
		);
		$inputs = $this->checkInputs($rules);

		if (!Authorization::is_accessed("transactions_pay_accept") and $inputs["date"] <= Date::time() - ( 86400 * 30)) {
			throw new InputValidationException("date");
		}

		if (self::checkBanktransferFollowup($inputs["bankaccount"]->bank_id, $inputs['followup'])) {
			throw new DuplicateRecord("followup");
		}

		$params = array(
			"bankaccount" => $inputs["bankaccount"]->id,
			"followup" => $inputs["followup"],
			"description" => $inputs['description'] ?? "",
		);

		if (isset($inputs['attachment'])) {
			$path = "storage/public/" . $inputs['attachment']->md5() . "." . $inputs['attachment']->getExtension();
			$storage = Packages::package("financial")->getFile($path);
			if (!$storage->exists()) {
				if (!$storage->getDirectory()->exists()) {
					$storage->getDirectory()->make(true);
				}
				$inputs['attachment']->copyTo($storage);
			}
			$params['attachment'] = $path;
		}

		$pay = $transaction->addPay(array(
			"date" => $inputs["date"],
			"method" => Transaction_pay::banktransfer,
			"price" => $inputs["price"],
			"status" => Transaction_pay::pending, //(Authorization::is_accessed("transactions_pay_accept") ? Transaction_pay::accepted : Transaction_pay::pending),
			"currency" => $transaction->currency->id,
			"params" => $params,
		));

		if ($pay) {
			if (Authentication::check()) {
				$log = new Log();
				$log->user = Authentication::getID();
				$log->type = logs\transactions\Pay::class;
				$log->title = t("financial.logs.transaction.pay", ["transaction_id" => $transaction->id]);
				$log->parameters = array(
					'pay' => Transaction_pay::byId($pay),
					'currency' => $transaction->currency,
				);
				$log->save();
			}
			$this->response->setStatus(true);
			$parameter = array();
			if ($token = Http::getURIData("token")) {
				$parameter["token"] = $token;
			}
			$this->response->setStatus(true);
			$url = ($transaction->remainPriceForAddPay() > 0) ? 'pay/banktransfer/' : 'view/';
			$this->response->Go(userpanel\url('transactions/'  . $url . $transaction->id, $parameter));
		} else {
			$this->response->setStatus(false);
		}
		return $this->response;
	}

	private function accept_handler($data, $newstatus){
		Authorization::haveOrFail("transactions_pay_accept");
		$action = '';
		if ($newstatus == Transaction_pay::accepted) {
			$action = 'accept';
		} elseif($newstatus == Transaction_pay::rejected) {
			$action = 'reject';
		}
		$view = View::byName(views\transactions\pay::class . '\\' . $action);
		$this->response->setView($view);
		$pay = self::getPay($data);
		$transaction = $pay->transaction;
		if ($pay->status != transaction_pay::pending) {
			throw new NotFound;
		}
		$view->setPay($pay);
		if(!http::is_post()){
			$this->response->setStatus(true);
			return $this->response;
		}
		$this->response->setStatus(false);
		$inputsRoles = array(
			'confrim' => array(
				'type' => 'bool'
			)
		);
		$inputs = $this->checkinputs($inputsRoles);
		if (!$inputs['confrim']) {
			throw new InputValidationException("confrim");
		}
		if ($newstatus == Transaction_pay::accepted) {
			self::payAcceptor($pay);
		} elseif($newstatus == Transaction_pay::rejected) {
			self::payRejector($pay);
		}
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
		return $this->response;
	}
	public function acceptPay($data){
		return $this->accept_handler($data, transaction_pay::accepted);
	}
	public function rejectPay($data){
		return $this->accept_handler($data, transaction_pay::rejected);
	}
	public function onlinePayView($data): Response
	{
		$transaction = $this->getTransactionForPay($data);

		if (!$this->transactionManager->canOnlinePay($transaction->id)) {
			throw new NotFound();
		}

		$view = View::byName(views\transactions\pay\OnlinePay::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);
		$view->setPayports($this->transactionManager->getOnlinePayports($transaction->id));

		$this->response->setStatus(true);
		return $this->response;
	}
	public function onlinePay($data)
	{
		$transaction = $this->getTransactionForPay($data);

		if (!$this->transactionManager->canOnlinePay($transaction->id)) {
			throw new NotFound();
		}

		$view = View::byName(views\transactions\pay\OnlinePay::class);
		$this->response->setView($view);

		$view->setTransaction($transaction);

		$payports = $this->transactionManager->getOnlinePayports($transaction->id);
		$view->setPayports($payports);

		$rules = array(
			'payport' => array(
				'type' => function ($data, $rule, $input) use ($payports) {
					foreach ($payports as $payport) {
						if ($data == $payport->id) {
							return $payport;
						}
					}
					throw new InputValidationException($input);
				},
			),
			'price' => array(
				'type' => 'number',
				'optional' => true,
				'float' => true,
				'min' => 0,
			),
			"currency" => array(
				"type" => Currency::class,
				'optional' => true,
				'default' => $transaction->currency,
			),
		);

		$view->setDataForm($this->inputsValue($rules));
		$inputs = $this->checkInputs($rules);
		if (
			!$inputs["payport"]->getCurrency($inputs["currency"]->id) or
			($transaction->currency->id != $inputs["currency"]->id and !$transaction->currency->hasRate($inputs["currency"]->id))
		) {
			$error = new Error('financial.transaction.payport.unSupportCurrencyTypeException');
			$error->setCode('financial.transaction.payport.unSupportCurrencyTypeException');
			$view->addError($error);
			$this->response->setStatus(false);
			return $this->response;
		}
		$remainPriceForAddPay = $transaction->currency->changeTo($transaction->remainPriceForAddPay(), $inputs["currency"]);
		if (!isset($inputs["price"])) {
			$inputs["price"] = $remainPriceForAddPay;
		}
		if ($inputs["price"] > $remainPriceForAddPay) {
			throw new InputValidationException("price");
		}
		$redirect = $inputs["payport"]->PaymentRequest($inputs['price'], $transaction, $inputs["currency"]);
		$this->response->setStatus(true);
		if ($redirect->method == Redirect::get) {
			$this->response->Go($redirect->getURL());
		} elseif ($redirect->method == Redirect::post) {
			$view = View::byName(views\transactions\pay\onlinepay\Redirect::class);
			$view->setTransaction($transaction);
			$view->setRedirect($redirect);
			$this->response->setView($view);
		}
		$this->response->setStatus(true);
		return $this->response;
	}
	private function redirectToConfig($transaction){
		if($transaction->status == transaction::paid and !$transaction->isConfigured()){
			$count = 0;
			$needConfigProduct = null;
			foreach($transaction->products as $product){
				if(!$product->configure){
					$count++;
					$needConfigProduct = $product;
				}
				if($count > 1)break;
			}
			if($count == 1){
				return userpanel\url('transactions/config/'.$needConfigProduct->id);
			}
		}
		return null;
	}
	public function delete(array $data): Response {
		Authorization::haveOrFail('transactions_delete');
		$transaction = $this->getTransaction($data["id"]);
		$view = View::byName(Views\transactions\Delete::class);
		$this->response->setView($view);
		$view->setTransactionData($transaction);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function destroy(?array $data = null): Response {
		Authorization::haveOrFail('transactions_delete');

		$transactions = array();

		if (isset($data["id"])) {
			$transaction = $this->getTransaction($data["id"]);
			$view = View::byName(Views\transactions\Delete::class);
			$this->response->setView($view);
			$view->setTransactionData($transaction);
			$this->response->Go(userpanel\url('transactions'));
			$transactions[] = $transaction;
		} else {
			$inputs = $this->checkInputs(array(
				"transactions" => array(
					"type" => "array",
					"convert-to-array" => true,
					"each" => Transaction::class,
				),
			));
			$transactions = $inputs["transactions"];
		}

		foreach ($transactions as $transaction) {
			$log = new Log();
			$log->user = Authentication::getUser();
			$log->type = logs\transactions\Delete::class;
			$log->title = t("financial.logs.transaction.delete", ["transaction_id" => $transaction->id]);
			$log->parameters = ['transaction' => $transaction];
			$log->save();
			$transaction->delete();
		}
		$this->response->setStatus(true);
		return $this->response;
	}
	public function edit($data){
		authorization::haveOrFail('transactions_edit');
		$view = view::byName("\\packages\\financial\\views\\transactions\\edit");
		$transaction = $this->getTransaction($data["id"]);

		$view->setTransactionData($transaction);
		$view->setCurrencies(currency::get());
		$inputsRules = [
			'title' => [
				'type' => 'string',
				'optional' => true
			],
			'user' => [
				'type' => 'number',
				'optional' => true
			],
			'create_at' => [
				'type' => 'date',
				'optional' => true
			],
			'expire_at' => [
				'type' => 'date',
				'optional' => true
			],
			'products' => [
				'optional' => true
			]
		];
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				if(isset($inputs['expire_at'])){
					if($inputs['expire_at']){
						$inputs['expire_at'] = date::strtotime($inputs['expire_at']);
					}else{
						unset($inputs['expire_at']);
					}
				}
				if(isset($inputs['create_at'])){
					if($inputs['create_at']){
						$inputs['create_at'] = date::strtotime($inputs['create_at']);
					}else{
						unset($inputs['create_at']);
					}
				}
				if(isset($inputs['user'])){
					if(!$inputs['user'] =user::byId($inputs['user'])){
						throw new inputValidation("user");
					}
				}
				if(isset($inputs['currency'])){
					if(!$inputs['currency'] = currency::byId($inputs['currency'])){
						throw new inputValidation('currency');
					}
				}else{
					$inputs['currency'] = $transaction->currency;
				}
				if(isset($inputs["expire_at"])){
					if(isset($inputs["create_at"])) {
						if($inputs["expire_at"] < $inputs["create_at"]){
							throw new inputValidation("expire_at");
						}
					}else{
						if($inputs["expire_at"] < $transaction->create_at){
							throw new inputValidation("expire_at");
						}
					}
				}
				if(isset($inputs["create_at"])){
					if(isset($inputs['expire_at'])){
						if ($inputs["create_at"] > $inputs['expire_at']) {
							throw new inputValidation("create_at");
						}
					}else{
						if ($inputs["create_at"] > $transaction->expire_at) {
							throw new inputValidation("create_at");
						}
					}
				}
				if(isset($inputs['products'])){
					if(!is_array($inputs['products'])){
						throw new inputValidation('products');
					}
					foreach($inputs['products'] as $product){
						if(isset($product['id']) and is_numeric($product['id'])){
							if(!transaction_product::byId($product['id'])){
								throw new inputValidation("product");
							}
						}
						foreach(['price', 'currency'] as $item){
							if(!isset($product[$item])){
								throw new inputValidation("product_{$item}");
							}
						}
						if(isset($product['discount']) and $product['discount'] < 0){
							throw new inputValidation('discount');
						}
						if($product['price'] == 0){
							throw new inputValidation('product_price');
						}
						if(!$product['currency'] = currency::byId($product['currency'])){
							throw new inputValidation("product_currency");
						}
						if($inputs['currency']->id != $product['currency']->id and !$product['currency']->hasRate($inputs['currency']->id)){
							throw new currency\UnChangableException($product['currency'], $inputs['currency']);
						}
					}
				}
				$productsData = array();
				if(isset($inputs['products'])){
					foreach($inputs['products'] as $row){
						if(isset($row['id']) and is_numeric($row['id'])){
							$product = transaction_product::byId($row['id']);
						}else{
							$product = new transaction_product;
							$product->transaction = $transaction->id;
							$product->method  = transaction_product::other;
						}

						if (!isset($row['vat']) or $row['vat'] < 0 or $row['vat'] > 100) {
							$row['vat'] = $product->vat ?? 0;
						}

						$product->title = $row['title'];
						$product->description = isset($row['description']) ? $row['description'] : null;
						$product->number = $row['number'];
						$product->price = $row['price'];
						$product->discount = $row['discount'];
						$product->currency = $row['currency'];
						$product->vat = $row['vat'];
						$product->save();
						$data = $product->toArray();
						$data["currency_title"] = $row['currency_title'];
						if (isset($row['id']) and !is_numeric($row['id'])) {
							$data["pId"] = $data["id"];
							$data["id"] = $row['id'];
						}
						$productsData[] = $data;
					}
				}
				$parameters = ['oldData' => []];
				if(isset($inputs['title']) and $transaction->title != $inputs['title']){
					$parameters['oldData']['title'] = $transaction->title;
					$transaction->title = $inputs['title'];
				}
				if (isset($inputs['expire_at']) and $transaction->expire_at != $inputs['expire_at']) {
					$parameters['oldData']['expire_at'] = $transaction->expire_at;
					$transaction->expire_at = $inputs['expire_at'];
					if ($transaction->status == transaction::expired and $inputs['expire_at'] > date::time()) {
						$transaction->status = transaction::unpaid;
					}
				}
				if (isset($inputs['create_at']) and $transaction->create_at != $inputs['create_at']) {
					$parameters['oldData']['create_at'] = $transaction->create_at;
					$transaction->create_at = $inputs['create_at'];
				}
				foreach(['currency', 'user'] as $item){
					if(isset($inputs[$item]) and $inputs[$item]->id != $transaction->$item->id){
						$parameters['oldData'][$item] = $transaction->$item;
						$transaction->$item = $inputs[$item]->id;
					}
				}
				if(isset($inputs['description'])){
					$transaction->setparam('description', $inputs['description']);
				}
				if($transaction->status == transaction::unpaid){
					if(isset($inputs['untriggered'])){
						$transaction->setParam('trigered_paid', 0);
					}
				}
				$transaction->save();
				$event = new events\transactions\edit($transaction);
				$event->trigger();
				$log = new log();
				$log->user = authentication::getUser();
				$log->type = logs\transactions\edit::class;
				$log->title = t("financial.logs.transaction.edit", ["transaction_id" => $transaction->id]);
				$log->parameters = $parameters;
				$log->save();
				$this->response->setStatus(true);
				$this->response->setData($productsData, "products");
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}catch(currency\UnChangableException $e){
				$error = new error();
				$error->setCode('financial.transaction.edit.currency.UnChangableException');
				$error->setMessage(t('error.financial.transaction.edit.currency.UnChangableException', [
					'currency' => $e->getCurrency()->title,
					'changeTo' => $e->getChangeTo()->title
				]));
				$view->addError($error);
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function add() {
		Authorization::haveOrFail('transactions_add');
		$view = View::byName(financialViews\Add::class);
		$view->setCurrencies(Currency::get());
		$this->response->setView($view);
		$this->response->setStatus(true);
		return $this->response;
	}

	public function store() {
		$this->response->setStatus(false);
		Authorization::haveOrFail('transactions_add');
		$view = View::byName(financialViews\Add::class);
		$view->setCurrencies(Currency::get());
		$this->response->setView($view);
		$inputsRules = array(
			'title' => array(
				'type' => 'string',
			),
			'user' => array(
				'type' => User::class,
			),
			'create_at' => array(
				'type' => 'date',
				'min' => 0,
				'unix' => true,
			),
			'expire_at' => array(
				'type' => 'date',
				'min' => 0,
				'unix' => true,
			),
			'products' => [
				'type' => function($data) {
					if (!is_array($data)) {
						throw new InputValidationException('products');
					}

					$products = [];
					foreach ($data as $key => $input) {
						if (!isset($input['title'])) {
							throw new InputValidationException("products[{$key}][title]");
						}

						if (!isset($input['price']) or $input['price'] == 0) {
							throw new InputValidationException("products[{$key}][price]");
						}

						if (isset($input['currency'])) {
							$query = new Currency();
							$query->where('id', $input['currency']);

							if (!$query->has()) {
								throw new InputValidationException("products[{$key}][currency]");
							}
						}

						foreach (['discount', 'vat'] as $item) {
							if (!isset($input[$item]) or $input[$item] < 0) {
								$input[$item] = 0;
							}
						}

						if ($input['vat'] > 100) {
							throw new InputValidationException('vat');
						}

						if (!isset($input['number']) or $input['number'] < 1) {
							$input['number'] = 1;
						}

						$input['method'] = Transaction_product::other;

						$products[] = $input;
					}

					return $products;
				},
			],
			'notification' => array(
				'type' => 'bool',
				'optional' => true,
				'default' => false
			),

		);
		$inputs = $this->checkinputs($inputsRules);
		$inputs['currency'] = Currency::getDefault($inputs['user']);
		
		if ($inputs['expire_at'] < $inputs['create_at']) {
			throw new InputValidationException('expire_at');
		}

		if (isset($inputs['description'])) {
			$inputs['params'] = [
				'description' => $inputs['description'],
			];

			unset($inputs['description']);
		}

		$transaction = $this->transactionManager->store($inputs, Authentication::getID(), $inputs['notification']);

		$this->response->setStatus(true);
		$this->response->Go(userpanel\url('transactions/view/'.$transaction->id));

		return $this->response;
	}

	private function getProduct($data){
		$types = authorization::childrenTypes();
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_products.transaction", "inner");
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "inner");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		$product = new transaction_product();
		$product->where("financial_transactions_products.id", $data['id']);
		$product = $product->getOne("financial_transactions_products.*");
		if(!$product){
			throw new NotFound();
		}
		return $product;
	}

	public function product_delete($data){
		authorization::haveOrFail('transactions_product_delete');
		$transaction_product = $this->getProduct($data);
		$view = view::byName("\\packages\\financial\\views\\transactions\\product_delete");
		$view->setProduct($transaction_product);
		if(http::is_post()){
			$this->response->setStatus(false);
			try {
				$transaction = $transaction_product->transaction;
				if(count($transaction->products) < 2){
					throw new illegalTransaction();
				}
				$log = new log();
				$log->user = authentication::getUser();
				$log->type = logs\transactions\edit::class;
				$log->title = t("financial.logs.transaction.edit", ["transaction_id" => $transaction->id]);
				$log->parameters = ['oldData' => ['products' => [$transaction_product]]];
				$log->save();
				$transaction_product->delete();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
			}catch(illegalTransaction $e){
				$error = new error();
				$error->setCode('illegalTransaction');
				$view->addError($error);
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function pay_delete($data){
		authorization::haveOrFail('transactions_pay_delete');
		$types = authorization::childrenTypes();
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_pays.transaction", "INNER");
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "INNER");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		$transaction_pay = new transaction_pay();
		$transaction_pay->where("financial_transactions_pays.id", $data['id']);
		$transaction_pay = $transaction_pay->getOne("financial_transactions_pays.*");
		if(!$transaction_pay){
			throw new NotFound();
		}
		$view = view::byName("\\packages\\financial\\views\\transactions\\pay\\delete");
		$view->setPayData($transaction_pay);
		if(http::is_post()){
			$this->response->setStatus(false);
			$inputsRoles = [
				'untriggered' => [
					'type' => 'number',
					'values' => [1],
					'optional' => true,
					'empty' => true
				]
			];
			try{
				$inputs = $this->checkinputs($inputsRoles);
				$transaction = $transaction_pay->transaction;
				if(count($transaction->pays) == 1){
					if(isset($inputs['untriggered']) and $inputs['untriggered']){
						$transaction->deleteParam('trigered_paid');
					}
				}
				$transaction_pay->delete();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function addingcredit()
	{
		authorization::haveOrFail('transactions_addingcredit');
		$view = view::byName("\\packages\\financial\\views\\transactions\\addingcredit");
		$this->response->setView($view);

		$types = Authorization::childrenTypes();
		if ($types) {
			$view->setClient(authentication::getID());
		}

		if (http::is_post()) {
			$inputsRules = array(
				'price' => array(
					'type' => 'float',
					'min' => 0,
				)
			);
	
			if ($types) {
				$inputsRules['client'] = array(
					'type' => User::class,
				);
			}
			$inputs = $this->checkinputs($inputsRules);

			if (!isset($inputs['client'])) {
				$inputs['client'] = Authentication::getUser();
			}

			$isOperator = $inputs['client']->id !== Authentication::getID();

			$transaction = $this->transactionManager->store([
				'title' => t('transaction.adding_credit'),
				'user' => $inputs['client']->id,
				'currency' => Currency::getDefault($inputs['client'])->id,
				'expire_at' => Date::time() + 86400,
				'products' => [
					[
						'title' => t('transaction.adding_credit', ['price' => $inputs['price']]),
						'price' => $inputs['price'],
						'type' => '\packages\financial\products\addingcredit',
						'method' => transaction_product::addingcredit
					],
				],
			], $isOperator ? Authentication::getID() : null, $isOperator);

			$this->response->setStatus(true);
			$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
		} else {
			$this->response->setStatus(true);
		}

		$this->response->setView($view);
		return $this->response;
	}
	public function acceptedView($data): Response {
		Authorization::haveOrFail('transactions_accept');
		$transaction = $this->getTransaction($data['id']);
		if (!in_array($transaction->status, [Transaction::UNPAID, Transaction::PENDING])) {
			throw new NotFound;
		}
		$view = View::byName(views\transactions\Accept::class);
		$this->response->setView($view);
		$view->setTransactionData($transaction);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function accepted($data): Response {
		Authorization::haveOrFail('transactions_accept');
		$transaction = $this->getTransaction($data['id']);
		if (!in_array($transaction->status, [Transaction::UNPAID, Transaction::PENDING])) {
			throw new NotFound;
		}
		$view = View::byName(views\transactions\Accept::class);
		$this->response->setView($view);
		$view->setTransactionData($transaction);

		$pendingPays = (new Transaction_Pay())
		->where("transaction", $transaction->id)
		->where("status", Transaction_Pay::PENDING)
		->get();
		foreach ($pendingPays as $pendingPay) {
			self::payAcceptor($pendingPay);
		}
		$payablePrice = $transaction->payablePrice();
		if ($payablePrice > 0) {
			$pay = $transaction->addPay(array(
				'date' => time(),
				'method' => Transaction_Pay::payaccepted,
				'price' => $payablePrice,
				'status' => Transaction_Pay::accepted,
				"currency" => $transaction->currency->id,
				'params' => array(
					'acceptor' => Authentication::getID(),
					'accept_date' => Date::time(),
				)
			));
			$log = new Log();
			$log->user = Authentication::getID();
			$log->type = logs\transactions\Pay::class;
			$log->title = t("financial.logs.transaction.pay", ["transaction_id" => $transaction->id]);
			$log->parameters = array(
				"pay" => (new Transaction_Pay)->byID($pay),
				"currency" => $transaction->currency,
			);
			$log->save();
		}
		$transaction->status = Transaction::paid;
		$transaction->save();
		$this->response->Go(userpanel\url("transactions/view/{$transaction->id}"));
		$this->response->setStatus(true);
		return $this->response;
	}
	public function config($data){
		authorization::haveOrFail('transactions_product_config');
		$product = $this->getProduct($data);
		$view = view::byName("\\packages\\financial\\views\\transactions\\product\\config");
		if($product->configure){
			throw new NotFound();
		}
		$product->config();
		$view->setProduct($product);
		if(http::is_post()){
			$this->response->setStatus(false);
			try{
				if($inputsRules = $product->getInputs()){
					$inputs = $this->checkinputs($inputsRules);
				}
				$product->config($inputs);
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url("transactions/view/".$product->transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function refund(): response {
		Authorization::haveOrFail("transactions_refund_add");
		$inputsRules = array(
			"refund_user" => array(
				"type" => "number",
				"optional" => true,
			),
			"refund_price" => array(
				"type" => "number",
			),
			"refund_account" => array(
				"type" => "number",
			),
		);
		$types = Authorization::childrenTypes();
		if (!$types) {
			unset($inputsRules["refund_user"]);
		}
		$inputs = $this->checkinputs($inputsRules);
		if (isset($inputs["refund_user"])) {
			if (!$inputs["refund_user"] = User::byId($inputs["refund_user"])) {
				throw new InputValidationException("refund_user");
			}
		} else {
			$inputs["refund_user"] = Authentication::getUser();
		}

		if (!$inputs["refund_account"] = (new Account)->where("user_id", $inputs["refund_user"]->id)->where("id", $inputs["refund_account"])->where("status", Account::Active)->getOne()) {
			throw new InputValidationException("refund_account");
		}

		$currency = Currency::getDefault($inputs["refund_user"]);
		$limits = Transaction::getCheckoutLimits($inputs["refund_user"]->id);

		if (
			$inputs["refund_price"] <= 0 or
			(
				isset($limits['currency']) and
				isset($limits['price']) and
				$inputs['refund_price'] < $limits['price']
			) or
			$inputs["refund_price"] > $inputs["refund_user"]->credit
		) {
			throw new InputValidationException("refund_price");
		}

		if (!Transaction::canCreateCheckoutTransaction($inputs["refund_user"]->id, $inputs["refund_price"])) {
			throw new Error('checkout_limits');
		}

		$expire = Options::get("packages.financial.refund_expire");
		if (!$expire) {
			$expire = 432000;
		}

		$isOperator = $inputs['refund_user']->id === Authentication::getID();

		$transaction = $this->transactionManager->store([
			'title' => t('packages.financial.transactions.title.refund'),
			'user' => $inputs['refund_user']->id,
			'currency' => $currency->id,
			'expire_at' => Date::time() + $expire,
			'products' => [
				[
					'title' => t('packages.financial.transactions.product.title.refund'),
					'price' => -$inputs['refund_price'],
					'description' => t('packages.financial.transactions.refund.description', array(
						'account_account' => $inputs['refund_account']->account ? $inputs['refund_account']->account : '-',
						'account_cart' => $inputs['refund_account']->cart ? $inputs['refund_account']->cart : '-',
						'account_shaba' => $inputs['refund_account']->shaba ? $inputs['refund_account']->shaba : '-',
						'account_owner' => $inputs['refund_account']->owner,
					)),
					'discount' => 0,
					'number' => 1,
					'method' => transaction_product::refund,
					'currency' => $currency->id,
					'params' => array(
						'current_user_credit' => DB::where('id', $inputs["refund_user"]->id)->getValue('userpanel_users', 'credit'),
						'bank-account' => $inputs['refund_account']->toArray(),
					),
				],
			],
		], $isOperator ? Authentication::getID() : null, $isOperator);

		$inputs['refund_user']->option('financial_last_checkout_time', Date::time());

		DB::where('id', $inputs["refund_user"]->id)->update('userpanel_users', [
			'credit' => DB::dec($inputs["refund_price"]),
		]);

		$inputs["refund_user"]->credit -= $inputs["refund_price"];
		$inputs["refund_user"]->save();
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
		return $this->response;
	}
	public function refundAccept($data) {
		Authorization::haveOrFail("transactions_refund_accept");
		$transaction = $this->getTransaction($data["transaction"]);
		if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() > 0) {
			throw new NotFound();
		}
		$inputs = $this->checkinputs(array(
			"refund_pay_info" => array(
				"type" => "string",
				"multiLine" => true,
			),
		));
		$transaction->setParam("refund_pay_info", $inputs["refund_pay_info"]);
		$transaction->addPay(array(
			"date" => date::time(),
			"method" => transaction_pay::payaccepted,
			"price" => $transaction->payablePrice(),
			"status" => transaction_pay::accepted,
			"currency" => $transaction->currency->id,
			"params" => array(
				"acceptor" => authentication::getID(),
				"accept_date" => date::time(),
			)
		));
		$transaction->status = transaction::paid;
		$transaction->save();

		(new Events\transactions\Refund\Accepted($transaction))->trigger();

		$this->response->setStatus(true);
		return $this->response;
	}
	public function refundReject($data) {
		Authorization::haveOrFail("transactions_refund_accept");
		$transaction = $this->getTransaction($data["transaction"]);
		if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() > 0) {
			throw new NotFound();
		}

		$inputs = $this->checkInputs(array(
			"refund_pay_info" => array(
				"type" => "string",
				"multiLine" => true,
			),
		));

		$transaction->setParam("refund_pay_info", $inputs["refund_pay_info"]);
		$transaction->setParam("refund_rejector", Authentication::getID());
		$transaction->status = Transaction::rejected;
		$transaction->save();
		
		$transaction->user->option('financial_last_checkout_time', 0);

		$transaction->user->credit += abs($transaction->payablePrice());
		$transaction->user->save();

		(new Events\transactions\Refund\Rejected($transaction))->trigger();

		$this->response->setStatus(true);
		return $this->response;
	}
	public function updatePay($data): response {
		Authorization::haveOrFail("transactions_pay_edit");
		$pay = self::getPay($data);
		$inputs = $this->checkinputs(array(
			"date" => array(
				"type" => "date",
				"unix" => true,
				"optional" => true,
			),
			"price" => array(
				"type" => "number",
				"min" => 0,
				"optional" => true,
			),
			"description" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
				"multiLine" => true,
			),
		));
		if (isset($inputs["price"]) and $inputs["price"] != $pay->price) {
			$payablePrice = abs($pay->transaction->payablePrice());
			if ($payablePrice == 0 and $inputs["price"] > $pay->price) {
				throw new InputValidationException("price");
			} else {
				$price = $inputs["price"] - $pay->price;
				if ($price > 0) {
					if ($payablePrice < $pay->currency->changeTo($price, $pay->transaction->currency)) {
						throw new InputValidationException("price");
					}
				}
			}
		}
		if (isset($inputs["date"]) and $inputs["date"] != $pay->date) {
			$pay->date = $inputs["date"];
		}
		if (isset($inputs["price"]) and $inputs["price"] != $pay->price) {
			$pay->price = $inputs["price"];
		}
		$pay->save();
		if (isset($inputs["description"])) {
			if ($inputs["description"]) {
				$pay->setParam("description", $inputs["description"]);
			} else {
				$pay->deleteParam("description");
			}
		}
		$this->response->setData(array(
			"id" => $pay->id,
			"date" => $pay->date,
			"price" => $pay->price,
			"currency" => $pay->currency->toArray(),
			"description" => $pay->param("description"),
			"status" => $pay->status,
		), "pay");
		$this->response->setStatus(true);
		return $this->response;
	}
	/**
	 * The view of reimburse (pay back) transaction
	 *
	 * @param array $data that should contains "transaction_id" index
	 * @return \packages\base\Response
	 */
	public function reimburseTransactionView(array $data): Response {
		Authorization::haveOrFail("transactions_reimburse");
		$transaction = $this->getTransaction($data["transaction_id"]);
		$pays = (new Transaction_Pay)
				->where("transaction", $transaction->id)
				->where("method",
						array(
							Transaction_Pay::CREDIT,
							Transaction_Pay::ONLINEPAY,
							Transaction_Pay::BANKTRANSFER,
						),
						"IN"
				)
				->where("status", Transaction_Pay::ACCEPTED)
		->get();

		if (empty($pays)) {
			throw new NotFound;
		}

		$view = View::byName(views\transactions\Reimburse::class);
		$view->setTransaction($transaction);
		$view->setPays($pays);

		$this->response->setView($view);
		$this->response->setStatus(true);
		return $this->response;
	}
	/**
	 * return transaction amount to user's credit and change transaction status
	 *
	 * @param array $data
	 * @return \packages\base\Response
	 */
	public function reimburseTransaction(array $data): Response {
		Authorization::haveOrFail("transactions_reimburse");
		$transaction = $this->getTransaction($data["transaction_id"]);
		$pays = (new Transaction_Pay)
				->where("transaction", $transaction->id)
				->where("method",
						array(
							Transaction_Pay::CREDIT,
							Transaction_Pay::ONLINEPAY,
							Transaction_Pay::BANKTRANSFER,
						),
						"IN"
				)
				->where("status", Transaction_Pay::ACCEPTED)
		->get();

		if (empty($pays)) {
			throw new NotFound;
		}

		$view = View::byName(views\transactions\Reimburse::class);
		$view->setTransaction($transaction);
		$this->response->setView($view);
		$view->setPays($pays);

		$myID = Authentication::getID();
		$userCurrency = Currency::getDefault($transaction->user);
		$reimbursePays = array();
		$amountOfReimburse = 0;
		foreach ($pays as $key => $pay) {
			try {
				$amountOfReimburse = $pay->currency->changeTo($pay->price, $userCurrency);
			} catch (Currency\UnChangableException $e) {
				unset($pays[$key]);
				$view->setPays($pays);
				continue;
			}
			$pay->status = Transaction_Pay::REIMBURSE;
			$pay->save();
			$pay->setParam("reimburse_by_user_id", $myID);
			$pay->setParam("user_credit_before_reimburse", (new User)->byID($transaction->user->id)->credit);

			DB::where("id", $transaction->user->id)
				->update("userpanel_users", array(
					"credit" => DB::inc($amountOfReimburse),
				));

			$reimbursePays[] = $pay;
		}

		if ($reimbursePays) {
			$log = new Log();
			$log->user = Authentication::getID();
			$log->type = logs\transactions\Reimburse::class;
			$log->title = t("financial.logs.transaction.pays.reimburse", array(
				"transaction_id" => $transaction->id,
			));
			$log->parameters = array(
				"pays" => $reimbursePays,
				"user_currency" => $userCurrency,
				"user" => (new User)->byID($transaction->user->id),
			);
			$log->save();

			try {
				$transactionTotalPrice = $transaction->totalPrice();
				$reimbursePaysByTransactionCurrency = $userCurrency->changeTo($amountOfReimburse, $transaction->currency);
				if (Safe::floats_cmp($transactionTotalPrice, $reimbursePaysByTransactionCurrency) == 0) {
					$transaction->status = Transaction::REFUND;
					$transaction->save();
				}
			} catch (Currency\UnChangableException $e) {
				throw new Error("financial.transaction.currency.UnChangableException.reimburse");
			}
		}


		$this->response->go(userpanel\url("transactions/view/{$transaction->id}"));
		$this->response->setStatus(true);
		return $this->response;
	}

	/**
	 * get the user's gain and spent chart data
	 */
	public function userStats(): Response {
		Authorization::haveOrFail("paid_user_profile");

		$inputs = $this->checkInputs(array(
			"type" => array(
				"type" => "string",
				"values" => array("gain", "spend"),
			),
			"from" => array(
				"type" => "date",
				"unix" => true,
			),
			"to" => array(
				"type" => "date",
				"unix" => true,
				"optional" => true,
				"default" => Date::time(),
			),
			"interval" => array(
				"type" => validators\IntervalValidator::class,
				"values" => array("1D", "1M", "1Y"),
			),
			"limit" => array(
				"type" => "uint",
				"max" => 30,
				"min" => 1,
				"optional" => true,
				"default" => 6,
			),
		));

		$me = Authentication::getUser();
		$spend = $inputs["type"] == "spend";
		$defaultCurrency = Currency::getDefault($me);
		$items = Stats::getStatsChartDataByUser($me, $spend, $inputs["from"], $inputs["to"], $inputs["interval"], $inputs["limit"]);

		$this->response->setData($defaultCurrency->toArray(), "currency");
		$this->response->setData($items, "items");
		$this->response->setStatus(true);
		return $this->response;
	}
}
class transactionNotFound extends NotFound{}
class illegalTransaction extends \Exception{}
class unAcceptedPrice extends \Exception{}