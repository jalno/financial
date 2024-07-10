<?php

namespace packages\financial\Controllers;

use packages\base\DB;
use packages\base\DB\DuplicateRecord;
use packages\base\DB\Parenthesis;
use packages\base\HTTP;
use packages\base\InputValidation;
use packages\base\InputValidationException;
use packages\base\NotFound;
use packages\base\Options;
use packages\base\Packages;
use packages\base\Response;
use packages\base\Utility\Safe;
use packages\base\View\Error;
use packages\base\Views\FormError;
use packages\financial\Authentication;
use packages\financial\Authorization;
use packages\financial\Bank\Account;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\Controller;
use packages\financial\Currency;
use packages\financial\Events;
use packages\financial\FinancialService;
use packages\financial\Logs;
use packages\financial\PayPort\Redirect;
use packages\financial\Stats;
use packages\financial\Transaction;
use packages\financial\TransactionPay;
use packages\financial\TransactionProduct;
use packages\financial\Validators;
use packages\financial\View;
use packages\financial\Views\Transactions as FinancialViews;
use packages\financial\Views\Transactions\Pay as PayView;
use packages\userpanel;
use packages\userpanel\Date;
use packages\userpanel\Log;
use packages\userpanel\User;

class Transactions extends Controller
{
    use Transactions\MergeTrait;

    public static function checkBanktransferFollowup(int $bank, string $code)
    {
        $account = new Account();
        $account->where('bank_id', $bank);
        $accounts = array_column($account->get(null, 'id'), 'id');
        if (!$accounts) {
            return false;
        }
        $banktransferPays = new TransactionPay();
        DB::join('financial_transactions_pays_params params1', 'params1.pay=financial_transactions_pays.id', 'INNER');
        DB::joinWhere('financial_transactions_pays_params params1', 'params1.name', 'bankaccount');
        DB::joinWhere('financial_transactions_pays_params params1', 'params1.value', $accounts, 'IN');
        DB::join('financial_transactions_pays_params params2', 'params2.pay=financial_transactions_pays.id', 'INNER');
        DB::joinWhere('financial_transactions_pays_params params2', 'params2.name', 'followup');
        DB::joinWhere('financial_transactions_pays_params params2', 'params2.value', $code);

        return $banktransferPays->has();
    }

    public static function getPay($data): TransactionPay
    {
        $check = Authentication::check();
        $isOperator = false;
        $types = [];
        if ($check) {
            $isOperator = Authorization::is_accessed('transactions_anonymous');
            $types = Authorization::childrenTypes();
        }
        $pay = new TransactionPay();
        $pay->with('currency');
        DB::join('financial_transactions', 'financial_transactions.id=financial_transactions_pays.transaction', 'INNER');
        $parenthesis = new Parenthesis();
        if ($check) {
            if ($isOperator) {
                $parenthesis->where('financial_transactions.user', null, 'is', 'or');
            }
            DB::join('userpanel_users', 'userpanel_users.id=financial_transactions.user', 'LEFT');
            if ($types) {
                $parenthesis->where('userpanel_users.type', $types, 'in', 'or');
            } else {
                $parenthesis->where('userpanel_users.id', Authentication::getID(), '=', 'or');
            }
            $pay->where($parenthesis);
        } elseif ($token = HTTP::getURIData('token')) {
            $pay->where('financial_transactions.token', $token);
        } else {
            throw new NotFound();
        }
        $pay->where('financial_transactions_pays.id', $data['pay']);
        $pay = $pay->getOne();
        if (!$pay) {
            throw new NotFound();
        }

        return $pay;
    }

    public static function payAcceptor(TransactionPay $pay)
    {
        $pay->status = TransactionPay::accepted;
        $pay->setParam('acceptor', Authentication::getID());
        $pay->setParam('accept_date', Date::time());
        $pay->save();
        $transaction = $pay->transaction;
        $log = new Log();
        $log->user = Authentication::getUser();
        $log->type = Logs\Transactions\Pay::class;
        $log->title = t('financial.logs.transaction.pay.accept', ['transaction_id' => $transaction->id, 'pay_id' => $pay->id]);
        $log->parameters = [
            'pay' => $pay,
            'currency' => $transaction->currency,
        ];
        $log->save();
    }

    public static function payRejector(TransactionPay $pay)
    {
        $pay->status = TransactionPay::rejected;
        $log = new Log();
        $log->user = Authentication::getUser();
        $log->type = Logs\Transactions\Pay::class;
        $pay->setParam('rejector', Authentication::getID());
        $pay->setParam('reject_date', Date::time());
        $pay->save();
        $transaction = $pay->transaction;
        $log->title = t('financial.logs.transaction.pay.reject', ['transaction_id' => $transaction->id, 'pay_id' => $pay->id]);
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

        if (Authentication::check()) {
            $this->page = HTTP::getURIData('page');
            $this->items_per_page = HTTP::getURIData('ipp');
            if ($this->page < 1) {
                $this->page = 1;
            }
            if ($this->items_per_page < 1) {
                $this->items_per_page = 25;
            }
            DB::pageLimit($this->items_per_page);
            $this->response = new Response();
        } elseif ($token = HTTP::getURIData('token')) {
            $transaction = new Transaction();
            $transaction->where('token', $token);
            if (!$transaction = $transaction->getOne()) {
                parent::response(Authentication::FailResponse());
            }
        } else {
            parent::response(Authentication::FailResponse());
        }
    }

    public function listtransactions()
    {
        Authorization::haveOrFail('transactions_list');
        $view = View::byName(FinancialViews\ListView::class);
        $this->response->setView($view);
        $canAccept = Authorization::is_accessed('transactions_pay_accept');
        $exporters = [];
        $exporter = null;
        if ($canAccept) {
            $exporter = new Events\Exporters();
            $exporter->trigger();
            $exporters = $exporter->get();
            $view->setExporters($exporters);
        }
        $types = Authorization::childrenTypes();
        $anonymous = Authorization::is_accessed('transactions_anonymous');
        $inputsRules = [
            'id' => [
                'type' => 'number',
                'optional' => true,
            ],
            'title' => [
                'type' => 'string',
                'optional' => true,
            ],
            'name' => [
                'type' => 'string',
                'optional' => true,
            ],
            'user' => [
                'type' => 'number',
                'optional' => true,
            ],
            'status' => [
                'type' => 'number',
                'values' => [Transaction::UNPAID, Transaction::PENDING, Transaction::PAID, Transaction::REFUND, Transaction::EXPIRED],
                'optional' => true,
            ],
            'download' => [
                'type' => 'string',
                'values' => ['csv'],
                'optional' => true,
            ],
            'create_from' => [
                'type' => 'date',
                'unix' => true,
                'optional' => true,
            ],
            'create_to' => [
                'type' => 'date',
                'unix' => true,
                'optional' => true,
            ],
            'word' => [
                'type' => 'string',
                'optional' => true,
            ],
            'comparison' => [
                'type' => 'string',
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ];
        if ($canAccept) {
            $inputsRules['download']['values'] = array_merge($exporter->getExporterNames(), $inputsRules['download']['values']);
            $inputsRules['refund'] = [
                'type' => 'bool',
                'optional' => true,
                'empty' => true,
                'default' => false,
            ];
        }
        $searched = false;
        $inputs = $this->checkinputs($inputsRules);
        if (!$canAccept) {
            $inputs['refund'] = false;
        }
        $view->setDataForm($this->inputsvalue($inputsRules));
        $transaction = new Transaction();
        $transaction->with('currency');
        foreach (['id', 'title', 'status', 'user'] as $item) {
            if (isset($inputs[$item])) {
                $comparison = $inputs['comparison'];
                if (in_array($item, ['id', 'status', 'user'])) {
                    $comparison = 'equals';
                }
                $transaction->where('financial_transactions.'.$item, $inputs[$item], $comparison);
                $searched = true;
            }
        }
        if (isset($inputs['create_from'])) {
            $transaction->where('financial_transactions.create_at', $inputs['create_from'], '>=');
            $searched = true;
        }
        if (isset($inputs['create_to'])) {
            $transaction->where('financial_transactions.create_at', $inputs['create_to'], '<');
            $searched = true;
        }
        if (isset($inputs['word']) and $inputs['word']) {
            $parenthesis = new Parenthesis();
            foreach (['title'] as $item) {
                if (!isset($inputs[$item])) {
                    $parenthesis->orWhere("financial_transactions.{$item}", $inputs['word'], $inputs['comparison']);
                }
            }
            $products = DB::subQuery();
            foreach (['title', 'description'] as $item) {
                $products->orWhere("financial_transactions_products.{$item}", $inputs['word'], $inputs['comparison']);
            }
            $parenthesis->orWhere('financial_transactions.id', $products->get('financial_transactions_products', null, 'financial_transactions_products.transaction'), 'IN');
            $searched = true;
            $transaction->where($parenthesis);
        }

        if ($anonymous) {
            $transaction->with('user', 'LEFT');
            $parenthesis = new Parenthesis();
            $parenthesis->where('userpanel_users.type', $types, 'in');
            $parenthesis->orWhere('financial_transactions.user', null, 'is');
            $transaction->where($parenthesis);
        } else {
            $transaction->with('user', 'INNER');
            if ($types) {
                $transaction->where('userpanel_users.type', $types, 'in');
            } else {
                $transaction->where('userpanel_users.id', Authentication::getID());
            }
        }
        if ($inputs['refund']) {
            $products = DB::subQuery();
            $products->where('financial_transactions_products.method', TransactionProduct::refund);
            $transaction->where('financial_transactions.id', $products->get('financial_transactions_products', null, 'financial_transactions_products.transaction'), 'IN');
            $searched = false;
        }
        $transaction->orderBy('financial_transactions.id', 'DESC');
        if (isset($inputs['download'])) {
            $transactions = $transaction->get();
            if (in_array($inputs['download'], $exporter->getExporterNames())) {
                $handler = $exporter->getByName($inputs['download'])->getHandler();
                $responseFile = (new $handler())->export($transactions);
                $this->response->setFile($responseFile);
                $this->response->forceDownload();
            }
        } else {
            if (!$searched) {
                $transaction->where('financial_transactions.status', Transaction::expired, '!=');
            }
            $transaction->pageLimit = $this->items_per_page;
            $transactions = $transaction->paginate($this->page);
            $view->setDataList($transactions);
            $view->setPaginate($this->page, DB::totalCount(), $this->items_per_page);
        }
        $this->response->setStatus(true);

        return $this->response;
    }

    public function transaction_view($data)
    {
        $transaction = $this->getTransaction($data['id']);
        $view = View::byName(FinancialViews\View::class);
        $view->setTransaction($transaction);
        $this->response->setStatus(true);
        try {
            $currency = $transaction->currency;
            $userCurrency = Currency::getDefault($transaction->user);
            if (Transaction::unpaid == $transaction->status) {
                $transaction->currency = Currency::getDefault($transaction->user);
                $transaction->price = $transaction->totalPrice();
                $transaction->save();
            }
            $transaction->deleteParam('UnChangableException');
        } catch (Currency\UnChangableException $e) {
            $this->response->setStatus(false);
            $error = new Error();
            $error->setCode('financial.transaction.currency.UnChangableException');
            $view->addError($error);
            $transaction->setParam('UnChangableException', true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    private function getTransaction($id): Transaction
    {
        $transaction = new Transaction();
        $parenthesis = new Parenthesis();
        if (Authorization::is_accessed('transactions_anonymous')) {
            $parenthesis->where('financial_transactions.user', null, 'is', 'or');
        }
        if (Authentication::check()) {
            $types = Authorization::childrenTypes();
            DB::join('userpanel_users', 'userpanel_users.id=financial_transactions.user', 'LEFT');
            if ($types) {
                $parenthesis->where('userpanel_users.type', $types, 'in', 'or');
            } else {
                $parenthesis->where('userpanel_users.id', Authentication::getID(), '=', 'or');
            }
            $transaction->where($parenthesis);
        } elseif ($token = HTTP::getURIData('token')) {
            $transaction->where('financial_transactions.token', $token);
        } else {
            throw new NotFound();
        }
        if (!$parenthesis->isEmpty()) {
            $transaction->where($parenthesis);
        }
        $transaction->where('financial_transactions.id', $id);
        $transaction = $transaction->getOne('financial_transactions.*');
        if (!$transaction) {
            throw new NotFound();
        }

        return $transaction;
    }

    /**
     * get transaction for pay
     * also check user permissions for this transaction and can add pay for this transaction.
     *
     * @see packages/financial/Transaction@canAddPay
     *
     * @return packages/financial/Transaction
     *
     * @throws packages/base/NotFound if can not find any transaction with t
     */
    private function getTransactionForPay($data): Transaction
    {
        $transaction = $this->getTransaction($data['transaction']);
        if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() < 0 or $transaction->param('UnChangableException')) {
            throw new NotFound();
        }

        return $transaction;
    }

    public function pay($data): Response
    {
        $transaction = $this->getTransactionForPay($data);

        $view = View::byName(PayView::class);
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

        $view = View::byName(PayView\Credit::class);
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

        $view = View::byName(PayView\Credit::class);
        $this->response->setView($view);

        $view->setTransaction($transaction);
        $view->setCredit($payer->credit);
        $view->setCurrency($transaction->currency);
        $view->setDataForm($payer->id, 'user');
        $view->setDataForm(min($transaction->remainPriceForAddPay(), $payer->credit), 'credit');

        $rules = [
            'credit' => [
                'type' => 'number',
                'min' => 0,
            ],
        ];

        if ($operator) {
            $rules['user'] = [
                'type' => User::class,
                'optional' => true,
                'query' => function ($query) use ($transaction, $operator) {
                    $query->where('id', [$transaction->user->id, $operator->id], 'IN');
                },
            ];
        }

        $inputs = $this->checkInputs($rules);

        if (!isset($inputs['user'])) {
            $inputs['user'] = $transaction->user;
        }

        $payerCurrency = Currency::getDefault($inputs['user']);

        if ($payerCurrency->id != $transaction->currency->id) {
            throw new InputValidationException('credit');
        }

        if ($inputs['credit'] > $inputs['user']->credit or $inputs['credit'] > $transaction->remainPriceForAddPay()) {
            throw new InputValidationException('credit');
        }

        $pay = $transaction->addPay([
            'method' => TransactionPay::credit,
            'price' => $inputs['credit'],
            'currency' => $transaction->currency->id,
            'params' => [
                'user' => $inputs['user']->id,
            ],
        ]);

        if ($pay) {
            $inputs['user']->credit -= $inputs['credit'];
            $inputs['user']->save();

            $log = new Log();
            $log->user = Authentication::getID();
            $log->type = Logs\Transactions\Pay::class;
            $log->title = t('financial.logs.transaction.pay', ['transaction_id' => $transaction->id]);
            $log->parameters = [
                'pay' => (new TransactionPay())->byID($pay),
                'currency' => $transaction->currency,
            ];
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

        $view = View::byName(PayView\BankTransfer::class);
        $this->response->setView($view);

        $view->setTransaction($transaction);

        $banktransferPays = (new TransactionPay())
            ->where('transaction', $transaction->id)
            ->where('method', TransactionPay::banktransfer)
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

        $view = View::byName(PayView\BankTransfer::class);
        $this->response->setView($view);

        $view->setTransaction($transaction);

        $banktransferPays = (new TransactionPay())
            ->where('transaction', $transaction->id)
            ->where('method', TransactionPay::banktransfer)
            ->get();

        $view->setBanktransferPays($banktransferPays);

        $accounts = $this->transactionManager->getBankAccountsForTransferPay($transaction->id);
        $view->setBankAccounts($accounts);

        $rules = [
            'bankaccount' => [
                'type' => function ($data, $rule, $input) use ($accounts) {
                    foreach ($accounts as $account) {
                        if ($account->id == $data) {
                            return $account;
                        }
                    }
                    throw new InputValidationException($input);
                },
            ],
            'price' => [
                'type' => 'float',
                'zero' => false,
                'min' => 0,
                'max' => $transaction->remainPriceForAddPay(),
            ],
            'followup' => [
                'type' => 'string',
            ],
            'description' => [
                'type' => 'string',
                'optional' => true,
            ],
            'date' => [
                'type' => 'date',
                'unix' => true,
            ],
            'attachment' => [
                'type' => 'file',
                'extension' => ['png', 'jpeg', 'jpg', 'gif', 'pdf', 'csf', 'docx'],
                'max-size' => 1024 * 1024 * 5,
                'optional' => true,
                'obj' => true,
            ],
        ];
        $inputs = $this->checkInputs($rules);

        if (!Authorization::is_accessed('transactions_pay_accept') and $inputs['date'] <= Date::time() - (86400 * 30)) {
            throw new InputValidationException('date');
        }

        if (self::checkBanktransferFollowup($inputs['bankaccount']->bank_id, $inputs['followup'])) {
            throw new DuplicateRecord('followup');
        }

        $params = [
            'bankaccount' => $inputs['bankaccount']->id,
            'followup' => $inputs['followup'],
            'description' => $inputs['description'] ?? '',
        ];

        if (isset($inputs['attachment'])) {
            $path = 'storage/public/'.$inputs['attachment']->md5().'.'.$inputs['attachment']->getExtension();
            $storage = Packages::package('financial')->getFile($path);
            if (!$storage->exists()) {
                if (!$storage->getDirectory()->exists()) {
                    $storage->getDirectory()->make(true);
                }
                $inputs['attachment']->copyTo($storage);
            }
            $params['attachment'] = $path;
        }

        $pay = $transaction->addPay([
            'date' => $inputs['date'],
            'method' => TransactionPay::banktransfer,
            'price' => $inputs['price'],
            'status' => TransactionPay::pending, // (Authorization::is_accessed("transactions_pay_accept") ? Transaction_pay::accepted : Transaction_pay::pending),
            'currency' => $transaction->currency->id,
            'params' => $params,
        ]);

        if ($pay) {
            if (Authentication::check()) {
                $log = new Log();
                $log->user = Authentication::getID();
                $log->type = Logs\Transactions\Pay::class;
                $log->title = t('financial.logs.transaction.pay', ['transaction_id' => $transaction->id]);
                $log->parameters = [
                    'pay' => TransactionPay::byId($pay),
                    'currency' => $transaction->currency,
                ];
                $log->save();
            }
            $this->response->setStatus(true);
            $parameter = [];
            if ($token = HTTP::getURIData('token')) {
                $parameter['token'] = $token;
            }
            $this->response->setStatus(true);
            $url = ($transaction->remainPriceForAddPay() > 0) ? 'pay/banktransfer/' : 'view/';
            $this->response->Go(userpanel\url('transactions/'.$url.$transaction->id, $parameter));
        } else {
            $this->response->setStatus(false);
        }

        return $this->response;
    }

    private function accept_handler($data, $newstatus)
    {
        Authorization::haveOrFail('transactions_pay_accept');
        $action = '';
        if (TransactionPay::accepted == $newstatus) {
            $action = 'accept';
        } elseif (TransactionPay::rejected == $newstatus) {
            $action = 'reject';
        }
        $view = View::byName(PayView::class.'\\'.$action);
        $this->response->setView($view);
        $pay = self::getPay($data);
        $transaction = $pay->transaction;
        if (TransactionPay::pending != $pay->status) {
            throw new NotFound();
        }
        $view->setPay($pay);
        if (!HTTP::is_post()) {
            $this->response->setStatus(true);

            return $this->response;
        }
        $this->response->setStatus(false);
        $inputsRoles = [
            'confrim' => [
                'type' => 'bool',
            ],
        ];
        $inputs = $this->checkinputs($inputsRoles);
        if (!$inputs['confrim']) {
            throw new InputValidationException('confrim');
        }
        if (TransactionPay::accepted == $newstatus) {
            self::payAcceptor($pay);
        } elseif (TransactionPay::rejected == $newstatus) {
            self::payRejector($pay);
        }
        $this->response->setStatus(true);
        $this->response->Go(userpanel\url('transactions/view/'.$transaction->id));

        return $this->response;
    }

    public function acceptPay($data)
    {
        return $this->accept_handler($data, TransactionPay::accepted);
    }

    public function rejectPay($data)
    {
        return $this->accept_handler($data, TransactionPay::rejected);
    }

    public function onlinePayView($data): Response
    {
        $transaction = $this->getTransactionForPay($data);

        if (!$this->transactionManager->canOnlinePay($transaction->id)) {
            throw new NotFound();
        }

        $view = View::byName(PayView\OnlinePay::class);
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

        $view = View::byName(PayView\OnlinePay::class);
        $this->response->setView($view);

        $view->setTransaction($transaction);

        $payports = $this->transactionManager->getOnlinePayports($transaction->id);
        $view->setPayports($payports);

        $rules = [
            'payport' => [
                'type' => function ($data, $rule, $input) use ($payports) {
                    foreach ($payports as $payport) {
                        if ($data == $payport->id) {
                            return $payport;
                        }
                    }
                    throw new InputValidationException($input);
                },
            ],
            'price' => [
                'type' => 'number',
                'optional' => true,
                'float' => true,
                'min' => 0,
            ],
            'currency' => [
                'type' => Currency::class,
                'optional' => true,
                'default' => $transaction->currency,
            ],
        ];

        $view->setDataForm($this->inputsValue($rules));
        $inputs = $this->checkInputs($rules);
        if (
            !$inputs['payport']->getCurrency($inputs['currency']->id)
            or ($transaction->currency->id != $inputs['currency']->id and !$transaction->currency->hasRate($inputs['currency']->id))
        ) {
            $error = new Error('financial.transaction.payport.unSupportCurrencyTypeException');
            $error->setCode('financial.transaction.payport.unSupportCurrencyTypeException');
            $view->addError($error);
            $this->response->setStatus(false);

            return $this->response;
        }
        $remainPriceForAddPay = $transaction->currency->changeTo($transaction->remainPriceForAddPay(), $inputs['currency']);
        if (!isset($inputs['price'])) {
            $inputs['price'] = $remainPriceForAddPay;
        }
        if ($inputs['price'] > $remainPriceForAddPay) {
            throw new InputValidationException('price');
        }
        $redirect = $inputs['payport']->PaymentRequest($inputs['price'], $transaction, $inputs['currency']);
        $this->response->setStatus(true);
        if (Redirect::get == $redirect->method) {
            $this->response->Go($redirect->getURL());
        } elseif (Redirect::post == $redirect->method) {
            $view = View::byName(PayView\OnlinePay\Redirect::class);
            $view->setTransaction($transaction);
            $view->setRedirect($redirect);
            $this->response->setView($view);
        }
        $this->response->setStatus(true);

        return $this->response;
    }

    private function redirectToConfig($transaction)
    {
        if (Transaction::paid == $transaction->status and !$transaction->isConfigured()) {
            $count = 0;
            $needConfigProduct = null;
            foreach ($transaction->products as $product) {
                if (!$product->configure) {
                    ++$count;
                    $needConfigProduct = $product;
                }
                if ($count > 1) {
                    break;
                }
            }
            if (1 == $count) {
                return userpanel\url('transactions/config/'.$needConfigProduct->id);
            }
        }

        return null;
    }

    public function delete(array $data): Response
    {
        Authorization::haveOrFail('transactions_delete');
        $transaction = $this->getTransaction($data['id']);
        $view = View::byName(FinancialViews\Delete::class);
        $this->response->setView($view);
        $view->setTransactionData($transaction);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function destroy(?array $data = null): Response
    {
        Authorization::haveOrFail('transactions_delete');

        $transactions = [];

        if (isset($data['id'])) {
            $transaction = $this->getTransaction($data['id']);
            $view = View::byName(FinancialViews\Delete::class);
            $this->response->setView($view);
            $view->setTransactionData($transaction);
            $this->response->Go(userpanel\url('transactions'));
            $transactions[] = $transaction;
        } else {
            $inputs = $this->checkInputs([
                'transactions' => [
                    'type' => 'array',
                    'convert-to-array' => true,
                    'each' => Transaction::class,
                ],
            ]);
            $transactions = $inputs['transactions'];
        }

        foreach ($transactions as $transaction) {
            $this->transactionManager->delete($transaction->id, Authentication::getID());
        }

        $this->response->setStatus(true);

        return $this->response;
    }

    public function edit($data)
    {
        Authorization::haveOrFail('transactions_edit');

        $transaction = $this->getTransaction($data['id']);

        $view = View::byName(FinancialViews\Edit::class);
        $this->response->setView($view);

        $view->setTransactionData($transaction);
        $view->setCurrencies(Currency::get());

        $inputsRules = [
            'title' => [
                'type' => 'string',
                'optional' => true,
            ],
            'user' => [
                'type' => User::class,
                'optional' => true,
            ],
            'create_at' => [
                'type' => 'date',
                'optional' => true,
                'unix' => true,
                'default' => $transaction->create_at,
            ],
            'expire_at' => [
                'type' => 'date',
                'optional' => true,
                'unix' => true,
                'default' => $transaction->expire_at,
            ],
            'products' => [
                'type' => function ($data) use ($transaction) {
                    if (!is_array($data)) {
                        throw new InputValidationException('products');
                    }

                    $products = [];

                    foreach ($data as $key => $item) {
                        $product = [];

                        if (isset($item['id'])) {
                            $query = new TransactionProduct();
                            $query->where('transaction', $transaction->id);
                            $query->where('id', $item['id']);

                            if (!$query->has()) {
                                throw new InputValidationException('product_id');
                            }

                            $product['id'] = $item['id'];
                        } else {
                            if (!isset($item['price'])) {
                                throw new InputValidationException('product_price');
                            }
                            if (!isset($item['title'])) {
                                throw new InputValidationException('product_title');
                            }

                            $product['method'] = TransactionProduct::other;
                        }

                        if (isset($item['currency'])) {
                            $query = new Currency();
                            $query->where('id', $item['currency']);
                            $currency = $query->getOne();

                            if (!$currency) {
                                throw new InputValidationException('product_currency');
                            }

                            if (!$currency->hasRate($transaction->currency->id)) {
                                $e = new Error('financial.transaction.edit.currency.UnChangableException');
                                $e->setMessage(t('error.financial.transaction.edit.currency.UnChangableException', [
                                    'currency' => $currency->title,
                                    'changeTo' => $transaction->currency->getChangeTo()->title,
                                ]));

                                throw $e;
                            }

                            $product['currency'] = $item['currency'];
                        }

                        if (isset($item['vat'])) {
                            $product['vat'] = ($item['vat'] < 0 or $item['vat'] > 100) ? 0 : $item['vat'];
                        }

                        if (isset($item['discount'])) {
                            $product['discount'] = max(0, $item['discount']);
                        }

                        if (isset($item['number'])) {
                            $item['number'] = max(1, $item['number']);
                        }

                        if (isset($item['price'])) {
                            if (0 == $item['price']) {
                                throw new InputValidationException('product_price');
                            }

                            $product['price'] = $item['price'];
                        }

                        if (isset($item['description'])) {
                            $product['description'] = $item['description'] ?: null;
                        }

                        if (isset($item['title'])) {
                            $product['title'] = $item['title'];
                        }

                        $products[] = $product;
                    }

                    return $products;
                },
                'optional' => true,
            ],
        ];

        if (HTTP::is_post()) {
            $inputs = $this->checkinputs($inputsRules);

            if ($inputs['expire_at'] and $inputs['create_at'] and $inputs['expire_at'] < $inputs['create_at']) {
                throw new InputValidation('expire_at');
            }

            if (isset($inputs['user'])) {
                $inputs['user'] = $inputs['user']->id;
            }

            $transaction = $this->transactionManager->update($transaction->id, $inputs, Authentication::getID());

            $products = array_map(fn (TransactionProduct $product) => [
                'id' => $product->id,
                'transaction' => $product->transaction->id,
                'title' => $product->title,
                'description' => $product->description,
                'price' => $product->price,
                'discount' => $product->discount,
                'number' => $product->number,
                'vat' => $product->vat,
                'currency_title' => $product->currency->title,
            ], $transaction->products);

            $this->response->setStatus(true);
            $this->response->setData($products, 'products');
        } else {
            $this->response->setStatus(true);
        }

        return $this->response;
    }

    public function add()
    {
        Authorization::haveOrFail('transactions_add');
        $view = View::byName(FinancialViews\Add::class);
        $view->setCurrencies(Currency::get());
        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function store()
    {
        $this->response->setStatus(false);
        Authorization::haveOrFail('transactions_add');
        $view = View::byName(FinancialViews\Add::class);
        $view->setCurrencies(Currency::get());
        $this->response->setView($view);
        $inputsRules = [
            'title' => [
                'type' => 'string',
            ],
            'user' => [
                'type' => User::class,
            ],
            'create_at' => [
                'type' => 'date',
                'min' => 0,
                'unix' => true,
            ],
            'expire_at' => [
                'type' => 'date',
                'min' => 0,
                'unix' => true,
            ],
            'products' => [
                'type' => function ($data) {
                    if (!is_array($data)) {
                        throw new InputValidationException('products');
                    }

                    $products = [];
                    foreach ($data as $key => $input) {
                        if (!isset($input['title'])) {
                            throw new InputValidationException("products[{$key}][title]");
                        }

                        if (!isset($input['price']) or 0 == $input['price']) {
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

                        $input['method'] = TransactionProduct::other;

                        $products[] = $input;
                    }

                    return $products;
                },
            ],
            'notification' => [
                'type' => 'bool',
                'optional' => true,
                'default' => false,
            ],
        ];
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

    private function getProduct($data)
    {
        $types = Authorization::childrenTypes();
        DB::join('financial_transactions', 'financial_transactions.id=financial_transactions_products.transaction', 'inner');
        DB::join('userpanel_users', 'userpanel_users.id=financial_transactions.user', 'inner');
        if ($types) {
            DB::where('userpanel_users.type', $types, 'in');
        } else {
            DB::where('userpanel_users.id', Authentication::getID());
        }
        $product = new TransactionProduct();
        $product->where('financial_transactions_products.id', $data['id']);
        $product = $product->getOne('financial_transactions_products.*');
        if (!$product) {
            throw new NotFound();
        }

        return $product;
    }

    public function product_delete($data)
    {
        Authorization::haveOrFail('transactions_product_delete');
        $transaction_product = $this->getProduct($data);
        $view = View::byName('\\packages\\financial\\views\\transactions\\product_delete');
        $view->setProduct($transaction_product);
        if (HTTP::is_post()) {
            $this->response->setStatus(false);
            try {
                $transaction = $transaction_product->transaction;
                if (count($transaction->products) < 2) {
                    throw new IllegalTransaction();
                }
                $log = new Log();
                $log->user = Authentication::getUser();
                $log->type = Logs\Transactions\Edit::class;
                $log->title = t('financial.logs.transaction.edit', ['transaction_id' => $transaction->id]);
                $log->parameters = ['oldData' => ['products' => [$transaction_product]]];
                $log->save();
                $transaction_product->delete();
                $this->response->setStatus(true);
                $this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
            } catch (IllegalTransaction $e) {
                $error = new Error();
                $error->setCode('illegalTransaction');
                $view->addError($error);
            }
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    public function pay_delete($data)
    {
        Authorization::haveOrFail('transactions_pay_delete');
        $types = Authorization::childrenTypes();
        DB::join('financial_transactions', 'financial_transactions.id=financial_transactions_pays.transaction', 'INNER');
        DB::join('userpanel_users', 'userpanel_users.id=financial_transactions.user', 'INNER');
        if ($types) {
            DB::where('userpanel_users.type', $types, 'in');
        } else {
            DB::where('userpanel_users.id', Authentication::getID());
        }
        $transaction_pay = new TransactionPay();
        $transaction_pay->where('financial_transactions_pays.id', $data['id']);
        $transaction_pay = $transaction_pay->getOne('financial_transactions_pays.*');
        if (!$transaction_pay) {
            throw new NotFound();
        }
        $view = View::byName('\\packages\\financial\\views\\transactions\\pay\\delete');
        $view->setPayData($transaction_pay);
        if (HTTP::is_post()) {
            $this->response->setStatus(false);
            $inputsRoles = [
                'untriggered' => [
                    'type' => 'number',
                    'values' => [1],
                    'optional' => true,
                    'empty' => true,
                ],
            ];
            try {
                $inputs = $this->checkinputs($inputsRoles);
                $transaction = $transaction_pay->transaction;
                if (1 == count($transaction->pays)) {
                    if (isset($inputs['untriggered']) and $inputs['untriggered']) {
                        $transaction->deleteParam('trigered_paid');
                    }
                }
                $transaction_pay->delete();
                $this->response->setStatus(true);
                $this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
            } catch (InputValidation $error) {
                $view->setFormError(FormError::fromException($error));
            }
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    public function addingcredit()
    {
        Authorization::haveOrFail('transactions_addingcredit');
        $view = View::byName('\\packages\\financial\\views\\transactions\\addingcredit');
        $this->response->setView($view);

        $types = Authorization::childrenTypes();
        if ($types) {
            $view->setClient(Authentication::getID());
        }

        if (HTTP::is_post()) {
            $inputsRules = [
                'price' => [
                    'type' => 'float',
                    'min' => 0,
                ],
            ];

            if ($types) {
                $inputsRules['client'] = [
                    'type' => User::class,
                ];
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
                        'method' => TransactionProduct::addingcredit,
                    ],
                ],
            ], $isOperator ? Authentication::getID() : null, $isOperator);

            $this->response->setStatus(true);
            $this->response->Go(userpanel\url('transactions/view/'.$transaction->id));
        } else {
            $this->response->setStatus(true);
        }

        $this->response->setView($view);

        return $this->response;
    }

    public function acceptedView($data): Response
    {
        Authorization::haveOrFail('transactions_accept');
        $transaction = $this->getTransaction($data['id']);
        if (!in_array($transaction->status, [Transaction::UNPAID, Transaction::PENDING])) {
            throw new NotFound();
        }
        $view = View::byName(FinancialViews\Accept::class);
        $this->response->setView($view);
        $view->setTransactionData($transaction);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function accepted($data): Response
    {
        Authorization::haveOrFail('transactions_accept');
        $transaction = $this->getTransaction($data['id']);
        if (!in_array($transaction->status, [Transaction::UNPAID, Transaction::PENDING])) {
            throw new NotFound();
        }
        $view = View::byName(FinancialViews\Accept::class);
        $this->response->setView($view);
        $view->setTransactionData($transaction);

        $pendingPays = (new TransactionPay())
        ->where('transaction', $transaction->id)
        ->where('status', TransactionPay::PENDING)
        ->get();
        foreach ($pendingPays as $pendingPay) {
            self::payAcceptor($pendingPay);
        }
        $payablePrice = $transaction->payablePrice();
        if ($payablePrice > 0) {
            $pay = $transaction->addPay([
                'date' => time(),
                'method' => TransactionPay::payaccepted,
                'price' => $payablePrice,
                'status' => TransactionPay::accepted,
                'currency' => $transaction->currency->id,
                'params' => [
                    'acceptor' => Authentication::getID(),
                    'accept_date' => Date::time(),
                ],
            ]);
            $log = new Log();
            $log->user = Authentication::getID();
            $log->type = Logs\Transactions\Pay::class;
            $log->title = t('financial.logs.transaction.pay', ['transaction_id' => $transaction->id]);
            $log->parameters = [
                'pay' => (new TransactionPay())->byID($pay),
                'currency' => $transaction->currency,
            ];
            $log->save();
        }
        $transaction->status = Transaction::paid;
        $transaction->save();
        $this->response->Go(userpanel\url("transactions/view/{$transaction->id}"));
        $this->response->setStatus(true);

        return $this->response;
    }

    public function config($data)
    {
        Authorization::haveOrFail('transactions_product_config');
        $product = $this->getProduct($data);
        $view = View::byName('\\packages\\financial\\views\\transactions\\product\\config');
        if ($product->configure) {
            throw new NotFound();
        }
        $product->config();
        $view->setProduct($product);
        if (HTTP::is_post()) {
            $this->response->setStatus(false);
            try {
                if ($inputsRules = $product->getInputs()) {
                    $inputs = $this->checkinputs($inputsRules);
                }
                $product->config($inputs);
                $this->response->setStatus(true);
                $this->response->Go(userpanel\url('transactions/view/'.$product->transaction->id));
            } catch (InputValidation $error) {
                $view->setFormError(FormError::fromException($error));
            }
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    public function refund(): Response
    {
        Authorization::haveOrFail('transactions_refund_add');
        $inputsRules = [
            'refund_user' => [
                'type' => 'number',
                'optional' => true,
            ],
            'refund_price' => [
                'type' => 'number',
            ],
            'refund_account' => [
                'type' => 'number',
            ],
        ];
        $types = Authorization::childrenTypes();
        if (!$types) {
            unset($inputsRules['refund_user']);
        }
        $inputs = $this->checkinputs($inputsRules);
        if (isset($inputs['refund_user'])) {
            if (!$inputs['refund_user'] = User::byId($inputs['refund_user'])) {
                throw new InputValidationException('refund_user');
            }
        } else {
            $inputs['refund_user'] = Authentication::getUser();
        }

        if (!$inputs['refund_account'] = (new Account())->where('user_id', $inputs['refund_user']->id)->where('id', $inputs['refund_account'])->where('status', Account::Active)->getOne()) {
            throw new InputValidationException('refund_account');
        }

        $currency = Currency::getDefault($inputs['refund_user']);
        $limits = Transaction::getCheckoutLimits($inputs['refund_user']->id);

        if (
            $inputs['refund_price'] <= 0
            or (
                isset($limits['currency'])
                and isset($limits['price'])
                and $inputs['refund_price'] < $limits['price']
            )
            or $inputs['refund_price'] > $inputs['refund_user']->credit
        ) {
            throw new InputValidationException('refund_price');
        }

        if (!Transaction::canCreateCheckoutTransaction($inputs['refund_user']->id, $inputs['refund_price'])) {
            throw new Error('checkout_limits');
        }

        $expire = Options::get('packages.financial.refund_expire');
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
                    'description' => t('packages.financial.transactions.refund.description', [
                        'account_account' => $inputs['refund_account']->account ? $inputs['refund_account']->account : '-',
                        'account_cart' => $inputs['refund_account']->cart ? $inputs['refund_account']->cart : '-',
                        'account_shaba' => $inputs['refund_account']->shaba ? $inputs['refund_account']->shaba : '-',
                        'account_owner' => $inputs['refund_account']->owner,
                    ]),
                    'discount' => 0,
                    'number' => 1,
                    'method' => TransactionProduct::refund,
                    'currency' => $currency->id,
                    'params' => [
                        'current_user_credit' => DB::where('id', $inputs['refund_user']->id)->getValue('userpanel_users', 'credit'),
                        'bank-account' => $inputs['refund_account']->toArray(),
                    ],
                ],
            ],
        ], $isOperator ? Authentication::getID() : null, $isOperator);

        $inputs['refund_user']->option('financial_last_checkout_time', Date::time());

        DB::where('id', $inputs['refund_user']->id)->update('userpanel_users', [
            'credit' => DB::dec($inputs['refund_price']),
        ]);

        $inputs['refund_user']->credit -= $inputs['refund_price'];
        $inputs['refund_user']->save();
        $this->response->setStatus(true);
        $this->response->Go(userpanel\url('transactions/view/'.$transaction->id));

        return $this->response;
    }

    public function refundAccept($data)
    {
        Authorization::haveOrFail('transactions_refund_accept');
        $transaction = $this->getTransaction($data['transaction']);
        if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() > 0) {
            throw new NotFound();
        }
        $inputs = $this->checkinputs([
            'refund_pay_info' => [
                'type' => 'string',
                'multiLine' => true,
            ],
        ]);
        $transaction->setParam('refund_pay_info', $inputs['refund_pay_info']);
        $transaction->addPay([
            'date' => Date::time(),
            'method' => TransactionPay::payaccepted,
            'price' => $transaction->payablePrice(),
            'status' => TransactionPay::accepted,
            'currency' => $transaction->currency->id,
            'params' => [
                'acceptor' => Authentication::getID(),
                'accept_date' => Date::time(),
            ],
        ]);
        $transaction->status = Transaction::paid;
        $transaction->save();

        (new Events\Transactions\Refund\Accepted($transaction))->trigger();

        $this->response->setStatus(true);

        return $this->response;
    }

    public function refundReject($data)
    {
        Authorization::haveOrFail('transactions_refund_accept');
        $transaction = $this->getTransaction($data['transaction']);
        if (!$transaction->canAddPay() or $transaction->remainPriceForAddPay() > 0) {
            throw new NotFound();
        }

        $inputs = $this->checkInputs([
            'refund_pay_info' => [
                'type' => 'string',
                'multiLine' => true,
            ],
        ]);

        $transaction->setParam('refund_pay_info', $inputs['refund_pay_info']);
        $transaction->setParam('refund_rejector', Authentication::getID());
        $transaction->status = Transaction::rejected;
        $transaction->save();

        $transaction->user->option('financial_last_checkout_time', 0);

        $transaction->user->credit += abs($transaction->payablePrice());
        $transaction->user->save();

        (new Events\Transactions\Refund\Rejected($transaction))->trigger();

        $this->response->setStatus(true);

        return $this->response;
    }

    public function updatePay($data): Response
    {
        Authorization::haveOrFail('transactions_pay_edit');
        $pay = self::getPay($data);
        $inputs = $this->checkinputs([
            'date' => [
                'type' => 'date',
                'unix' => true,
                'optional' => true,
            ],
            'price' => [
                'type' => 'number',
                'min' => 0,
                'optional' => true,
            ],
            'description' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
                'multiLine' => true,
            ],
        ]);
        if (isset($inputs['price']) and $inputs['price'] != $pay->price) {
            $payablePrice = abs($pay->transaction->payablePrice());
            if (0 == $payablePrice and $inputs['price'] > $pay->price) {
                throw new InputValidationException('price');
            } else {
                $price = $inputs['price'] - $pay->price;
                if ($price > 0) {
                    if ($payablePrice < $pay->currency->changeTo($price, $pay->transaction->currency)) {
                        throw new InputValidationException('price');
                    }
                }
            }
        }
        if (isset($inputs['date']) and $inputs['date'] != $pay->date) {
            $pay->date = $inputs['date'];
        }
        if (isset($inputs['price']) and $inputs['price'] != $pay->price) {
            $pay->price = $inputs['price'];
        }
        $pay->save();
        if (isset($inputs['description'])) {
            if ($inputs['description']) {
                $pay->setParam('description', $inputs['description']);
            } else {
                $pay->deleteParam('description');
            }
        }
        $this->response->setData([
            'id' => $pay->id,
            'date' => $pay->date,
            'price' => $pay->price,
            'currency' => $pay->currency->toArray(),
            'description' => $pay->param('description'),
            'status' => $pay->status,
        ], 'pay');
        $this->response->setStatus(true);

        return $this->response;
    }

    /**
     * The view of reimburse (pay back) transaction.
     *
     * @param array $data that should contains "transaction_id" index
     */
    public function reimburseTransactionView(array $data): Response
    {
        Authorization::haveOrFail('transactions_reimburse');
        $transaction = $this->getTransaction($data['transaction_id']);
        $pays = (new TransactionPay())
                ->where('transaction', $transaction->id)
                ->where('method',
                    [
                        TransactionPay::CREDIT,
                        TransactionPay::ONLINEPAY,
                        TransactionPay::BANKTRANSFER,
                    ],
                    'IN'
                )
                ->where('status', TransactionPay::ACCEPTED)
        ->get();

        if (empty($pays)) {
            throw new NotFound();
        }

        $view = View::byName(FinancialViews\Reimburse::class);
        $view->setTransaction($transaction);
        $view->setPays($pays);

        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    /**
     * return transaction amount to user's credit and change transaction status.
     */
    public function reimburseTransaction(array $data): Response
    {
        Authorization::haveOrFail('transactions_reimburse');
        $transaction = $this->getTransaction($data['transaction_id']);
        $pays = (new TransactionPay())
                ->where('transaction', $transaction->id)
                ->where('method',
                    [
                        TransactionPay::CREDIT,
                        TransactionPay::ONLINEPAY,
                        TransactionPay::BANKTRANSFER,
                    ],
                    'IN'
                )
                ->where('status', TransactionPay::ACCEPTED)
        ->get();

        if (empty($pays)) {
            throw new NotFound();
        }

        $view = View::byName(FinancialViews\Reimburse::class);
        $view->setTransaction($transaction);
        $this->response->setView($view);
        $view->setPays($pays);

        $myID = Authentication::getID();
        $userCurrency = Currency::getDefault($transaction->user);
        $reimbursePays = [];
        $amountOfReimburse = 0;
        foreach ($pays as $key => $pay) {
            try {
                $amountOfReimburse = $pay->currency->changeTo($pay->price, $userCurrency);
            } catch (Currency\UnChangableException $e) {
                unset($pays[$key]);
                $view->setPays($pays);
                continue;
            }
            $pay->status = TransactionPay::REIMBURSE;
            $pay->save();
            $pay->setParam('reimburse_by_user_id', $myID);
            $pay->setParam('user_credit_before_reimburse', (new User())->byID($transaction->user->id)->credit);

            DB::where('id', $transaction->user->id)
                ->update('userpanel_users', [
                    'credit' => DB::inc($amountOfReimburse),
                ]);

            $reimbursePays[] = $pay;
        }

        if ($reimbursePays) {
            $log = new Log();
            $log->user = Authentication::getID();
            $log->type = Logs\Transactions\Reimburse::class;
            $log->title = t('financial.logs.transaction.pays.reimburse', [
                'transaction_id' => $transaction->id,
            ]);
            $log->parameters = [
                'pays' => $reimbursePays,
                'user_currency' => $userCurrency,
                'user' => (new User())->byID($transaction->user->id),
            ];
            $log->save();

            try {
                $transactionTotalPrice = $transaction->totalPrice();
                $reimbursePaysByTransactionCurrency = $userCurrency->changeTo($amountOfReimburse, $transaction->currency);
                if (0 == Safe::floats_cmp($transactionTotalPrice, $reimbursePaysByTransactionCurrency)) {
                    $transaction->status = Transaction::REFUND;
                    $transaction->save();
                }
            } catch (Currency\UnChangableException $e) {
                throw new Error('financial.transaction.currency.UnChangableException.reimburse');
            }
        }

        $this->response->go(userpanel\url("transactions/view/{$transaction->id}"));
        $this->response->setStatus(true);

        return $this->response;
    }

    /**
     * get the user's gain and spent chart data.
     */
    public function userStats(): Response
    {
        Authorization::haveOrFail('paid_user_profile');

        $inputs = $this->checkInputs([
            'type' => [
                'type' => 'string',
                'values' => ['gain', 'spend'],
            ],
            'from' => [
                'type' => 'date',
                'unix' => true,
            ],
            'to' => [
                'type' => 'date',
                'unix' => true,
                'optional' => true,
                'default' => Date::time(),
            ],
            'interval' => [
                'type' => Validators\IntervalValidator::class,
                'values' => ['1D', '1M', '1Y'],
            ],
            'limit' => [
                'type' => 'uint',
                'max' => 30,
                'min' => 1,
                'optional' => true,
                'default' => 6,
            ],
        ]);

        $me = Authentication::getUser();
        $spend = 'spend' == $inputs['type'];
        $defaultCurrency = Currency::getDefault($me);
        $items = Stats::getStatsChartDataByUser($me, $spend, $inputs['from'], $inputs['to'], $inputs['interval'], $inputs['limit']);

        $this->response->setData($defaultCurrency->toArray(), 'currency');
        $this->response->setData($items, 'items');
        $this->response->setStatus(true);

        return $this->response;
    }
}

class TransactionNotFound extends NotFound
{
}
