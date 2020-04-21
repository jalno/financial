<?php
namespace themes\clipone\views\transactions;
use packages\userpanel;
use packages\userpanel\{date, User};
use packages\base\{packages, view\error, db\dbObject, frontend\theme, db\Parenthesis};
use themes\clipone\{viewTrait, navigation, views\listTrait, views\formTrait, navigation\menuItem, views\TransactionTrait};
use packages\financial\{Transaction, transaction_product as TransactionProduct, Authorization,
						Authentication, Bank\Account, Currency, views\transactions\listview as transactionsListView};

class listview extends transactionsListView{
	use viewTrait, listTrait, formTrait, TransactionTrait;
	protected $multiuser = false;
	protected $canRefund = false;
	protected $canAccept = false;
	protected $user;
	private $exporters = array();
	public function __beforeLoad(){
		$this->setTitle([
			t('transactions'),
			t('list')
		]);
		$this->setButtons();
		$this->multiuser = (bool) authorization::childrenTypes();
		$this->setDates();
		navigation::active("transactions/list");
		if(empty($this->getTransactions())){
			$this->addNotFoundError();
		}
		$this->canRefund = authorization::is_accessed("transactions_refund_add");
		$this->canAccept = Authorization::is_accessed("transactions_pay_accept");
		if ($this->canRefund) {
			$this->user = authentication::getUser();
			$this->user->currency = currency::getDefault($this->user);
			if ($this->multiuser) {
				if ($user = $this->getDataForm("refund_user")) {
					if ($user = user::byId($user)) {
						$this->setDataForm($user->getFullName(), "refund_user_name");
					}
				} else {
					$this->setDataForm($this->user->id, "refund_user");
					$this->setDataForm($this->user->getFullName(), "refund_user_name");
				}
			}
		}
	}
	private function addNotFoundError(){
		$error = new error();
		$error->setType(error::NOTICE);
		$error->setCode('financial.transaction.notfound');
		$btns = [];
		if(packages::package('ticketing')){
			$btns[] = [
				'type' => 'btn-teal',
				'txt' => t('ticketing.add'),
				'link' => userpanel\url('ticketing/new')
			];
		}
		if($this->canAdd){
			$btns[] = [
				'type' => 'btn-success',
				'txt' => t('financial.transaction.add'),
				'link' => userpanel\url('transactions/new')
			];
		}
		if($this->canAddingCredit){
			$btns[] = [
				'type' => 'btn-success',
				'txt' => t('transaction.adding_credit'),
				'link' => userpanel\url('transactions/addingcredit')
			];
		}
		$error->setData($btns, 'btns');
		$this->addError($error);
	}
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if(self::$navigation){
			$item = new menuItem("transactions");
			$item->setTitle(t("packages.financial.transactions"));
			$item->setURL(userpanel\url('transactions'));
			$item->setIcon('fa fa-money');
			navigation::addItem($item);
			if (packages::package("dakhl")) {
				$invoices = navigation::getByName("invoices");
				$bankAccounts = navigation::getByName("bankAccounts");
				if ($invoices or $bankAccounts) {
					$dakhl = new menuItem("dakhl");
					$dakhl->setTitle("دخل");
					$dakhl->setIcon("fa fa-tachometer");
					navigation::addItem($dakhl);
					if ($invoices) {
						navigation::removeItem($invoices);
						$dakhl->addItem($invoices);
					}
					if ($bankAccounts) {
						navigation::removeItem($bankAccounts);
						$dakhl->addItem($bankAccounts);
					}
				}
			}
		}
	}
	public function setButtons(){
		$this->setButton('transactions_view', $this->canView, [
			'title' => t("packages.financial.view"),
			'icon' => 'fa fa-files-o',
			'classes' => ['btn', 'btn-xs', 'btn-green']
		]);
		$this->setButton('transactions_edit', $this->canEdit, [
			'title' => t("packages.financial.edit"),
			'icon' => 'fa fa-edit',
			'classes' => ['btn', 'btn-xs', 'btn-teal']
		]);
		$this->setButton('transactions_delete', $this->canDel, [
			'title' => t("packages.financial.delete"),
			'icon' => 'fa fa-times',
			'classes' => ['btn', 'btn-xs', 'btn-bricky']
		]);
	}
	public function setDates(){
		foreach($this->dataList as $key => $data){
			$this->dataList[$key]->create_at = date::format("Y/m/d H:i:s", $data->create_at);
		}
	}
	public function getComparisonsForSelect(){
		return [
			[
				'title' => t('search.comparison.contains'),
				'value' => 'contains'
			],
			[
				'title' => t('search.comparison.equals'),
				'value' => 'equals'
			],
			[
				'title' => t('search.comparison.startswith'),
				'value' => 'startswith'
			]
		];
	}
	public function setExporters(array $exporters) {
		$this->exporters = $exporters;
	}
	protected function getStatusForSelect(){
		return [
			[
				'title' => '',
				'value' => ''
			],
			[
				'title' => t('transaction.unpaid'),
				'value' => transaction::unpaid
			],
			[
				'title' => t('transaction.paid'),
				'value' => transaction::paid
			],
			[
				'title' => t('transaction.refund'),
				'value' => transaction::refund
			],
			[
				'title' => t('transaction.status.expired'),
				'value' => transaction::expired
			]
		];
	}
	protected function getBanksAccountForSelect(): array {
		$types = authorization::childrenTypes();
		$accounts = array();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", authentication::getID());
		}
		$account->where("financial_banks_accounts.status", Account::Active);
		foreach ($account->get(null, array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*")) as $account) {
			if ($types) {
				$item = array(
					"title" => "{$account->bank->title} - {$account->user->getFullName()} [{$account->cart}]",
					"value" => $account->id,
				);
			} else {
				$item = array(
					"title" => "{$account->bank->title} [{$account->cart}]",
					"value" => $account->id,
				);
			}
			$item["data"] = array(
				"user" => $account->user_id,
			);
			$accounts[] = $item;
		}
		return $accounts;
	}
	protected function hasRefundTransaction(): bool {
		$types = Authorization::childrenTypes();
		$anonymous = Authorization::is_accessed("transactions_anonymous");
		$transaction = new Transaction();
		$transaction->join(TransactionProduct::class, null, "INNER", "transaction");
		if($anonymous){
			$transaction->join(User::class, "user", "LEFT");
			if ($types) {
				$parenthesis = new Parenthesis();
				$parenthesis->where("userpanel_users.type", $types, "in");
				$parenthesis->orWhere("financial_transactions.user", null, "is");
				$transaction->where($parenthesis);
			} else {
				$transaction->where("financial_transactions.user", null, "is");
			}
		} else {
			$transaction->join(User::class, "user", "INNER");
			if ($types) {
				$transaction->where("userpanel_users.type", $types, "in");
			} else {
				$transaction->where("financial_transactions.user", Authentication::getID());
			}
		}
		$transaction->where("financial_transactions.status", Transaction::unpaid);
		$transaction->where("financial_transactions_products.method", TransactionProduct::refund);
		return $transaction->has();
	}
	protected function getExportOptionsForSelect() {
		$options = array();
		foreach ($this->exporters as $exporter) {
			$options[] = array(
				"title" => t("packages.financial.export.{$exporter->getName()}"),
				"value" => $exporter->getName(),
				"data" => array(
					"refund" => true,
				),
			);
		}
		return $options;
	}
}
