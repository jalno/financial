<?php

namespace packages\financial\controllers\PaymentMethods;

use packages\base\DB;
use packages\base\NotFound;
use packages\base\Response;
use packages\base\View;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\controllers\Transactions;
use packages\financial\Currency;
use packages\financial\logs\transactions\Pay as PayLog;
use packages\financial\PaymentMethdos\CreditPaymentMethod;
use packages\financial\TransactionManager;
use packages\financial\Transaction_pay;
use packages\userpanel\Authentication;
use packages\userpanel\Authorization;
use packages\userpanel\Controller;
use packages\userpanel\Log;
use themes\clipone\views\financial\PaymentMethods\CreditView;
use function packages\userpanel\url;
/**
 * @property Response $response
 */
class CreditController extends Controller
{
    /**
     * @var bool
     */
    protected $authentication = true;
    private ITransactionManager $transactionManager;

    public function __construct(?ITransactionManager $transactionManager = null)
    {
        parent::__construct();
        $this->transactionManager = $transactionManager ?: TransactionManager::getInstance();
    }

    public function view(array $data): Response
    {
        $transaction = Transactions::getTransaction($data['id']);

        if (
            !$this->transactionManager->canPay($transaction) or
            !CreditPaymentMethod::getInstance()->canPay($transaction) or
            $transaction->currency->id != Currency::getDefault($transaction->user)->id or
            ($transaction->user->credit <= 0 and !Authorization::is_accessed('payment_method_credit_debt', 'financial'))
        ) {
            throw new NotFound();
        }

        $user = $transaction->user;
        /**
         * @var CreditView
         */
        $view = View::byName(CreditView::class);
        $view->transaction = $transaction;
        $view->price = $user->credit > 0 ? $user->credit : -1*$transaction->remainPriceForAddPay();

        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function pay(array $data): Response
    {
        $transaction = Transactions::getTransaction($data['id']);
        $paymentMethod = CreditPaymentMethod::getInstance();
        $canCreateDebt = Authorization::is_accessed('payment_method_credit_debt', 'financial');

        if (
            !$transaction->canAddPay() or
            $transaction->remainPriceForAddPay() < 0 or
            $transaction->param('UnChangableException') or
            !$paymentMethod->canPay($transaction) or
            $transaction->currency->id != Currency::getDefault($transaction->user)->id or
            ($transaction->user->credit <= 0 and !$canCreateDebt)
        ) {
            throw new NotFound();
        }

        $user = $transaction->user;
        /**
         * @var CreditView
         */
        $view = View::byName(CreditView::class);
        $view->transaction = $transaction;
        $view->price = $user->credit > 0 ? $user->credit : -1*$transaction->remainPriceForAddPay();

        $this->response->setView($view);

        $inputs = $this->checkInputs([
            'price' => [
                'type' => 'number',
                'negetive' => $canCreateDebt,
                'zero' => false,
                'max' => $view->price,
            ],
        ]);

        DB::startTransaction();

        $pay = $transaction->addPay([
			'method' => $paymentMethod->getName(),
			'price' => abs($inputs['price']),
			"currency" => $transaction->currency->id,
			'params' => [
				'user' => $user->id,
            ],
		]);

        if (!$pay) {
            DB::rollback();
            throw new \Exception('Can not add pay to transaction '.$transaction->id);
        }

        $creditBeforePay = DB::where('id', $user->id)->getValue('userpanel_users', 'credit');

        DB::where('id', $user->id);
        $result = DB::update('userpanel_users', [
            'credit' => DB::dec(abs($inputs['price'])),
        ]);

        if (!$result) {
            DB::rollback();
            throw new \Exception('Can not update user credit for pay transaction '.$transaction->id);
        }

        $creditAfterPay = DB::where('id', $user->id)->getValue('userpanel_users', 'credit');

        $log = new Log();
        $log->user = Authentication::getID();
        $log->type = PayLog::class;
        $log->title = t("financial.logs.transaction.pay", ["transaction_id" => $transaction->id]);
        $log->parameters = array(
            'price' => $inputs['price'],
            'user_credit_before_pay' => $creditBeforePay,
            'user_credit_after_pay' => $creditAfterPay,
            'pay' => (new Transaction_pay)->byID($pay),
            'currency' => $transaction->currency,
        );
        $result = $log->save();

        if (!$result) {
            DB::rollback();
            throw new \Exception('Can not store pay log for pay transaction '.$transaction->id);
        }

        DB::commit();

        $this->response->setStatus(true);
        $this->response->Go(url('transactions/view/'.$transaction->id));

        return $this->response;
    }
}
