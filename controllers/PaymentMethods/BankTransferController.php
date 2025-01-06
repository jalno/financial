<?php

namespace packages\financial\controllers\PaymentMethods;

use packages\base\Date;
use packages\base\Http;
use packages\base\InputValidationException;
use packages\base\NotFound;
use packages\base\Packages;
use packages\base\Response;
use packages\base\View;
use packages\financial\controllers\Transactions;
use packages\financial\logs\transactions\Pay as PayLog;
use packages\financial\PaymentMethdos\BankTransferPaymentMethod;
use packages\financial\Transaction_pay as TransactionPay;
use packages\financial\TransactionManager;
use packages\userpanel\Authentication;
use packages\userpanel\Authorization;
use packages\userpanel\Controller;
use packages\userpanel\Log;
use themes\clipone\views\financial\PaymentMethods\BankTransferView;
use function packages\userpanel\url;

/**
 * @property Response $response
 */
class BankTransferController extends Controller
{
    /**
     * @var bool
     */
    protected $authentication = false;
    private TransactionManager $transactionManager;

    public function __construct(?TransactionManager $transactionManager = null)
    {
        parent::__construct();
        $this->transactionManager = $transactionManager ?: TransactionManager::getInstance();
    }

    public function view(array $data): Response
    {
        $transaction = Transactions::getTransaction($data['id']);
        $paymentMethod = BankTransferPaymentMethod::getInstance();

        if (
            !$this->transactionManager->canPay($transaction) or
            !$paymentMethod->canPay($transaction)
        ) {
            throw new NotFound();
        }

        /**
         * @var BankTransferView
         */
        $view = View::byName(BankTransferView::class);
        $view->transaction = $transaction;
        $view->bankAccounts = $paymentMethod->getBankAccountsForPay($transaction);

        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function pay(array $data): Response
    {
        $transaction = Transactions::getTransaction($data['id']);
        $paymentMethod = BankTransferPaymentMethod::getInstance();

        if (
            !$this->transactionManager->canPay($transaction) or
            !$paymentMethod->canPay($transaction)
        ) {
            throw new NotFound();
        }

        $accounts = $paymentMethod->getBankAccountsForPay($transaction);
        /**
         * @var BankTransferView
         */
        $view = View::byName(BankTransferView::class);
        $view->transaction = $transaction;
        $view->bankAccounts = $accounts;

        $this->response->setView($view);

        $inputs = $this->checkInputs([
            "bankaccount" => [
                "type" => function ($data, $rule, $input) use ($accounts) {
                    foreach ($accounts as $account) {
                        if ($account->id == $data) {
                            return $account;
                        }
                    }
                    throw new InputValidationException($input);
                },
            ],
            "price" => [
                "type" => "float",
                "zero" => false,
                "min" => 0,
            ],
            "followup" => [
                "type" => "string",
            ],
            "description" => [
                "type" => "string",
                "optional" => true,
            ],
            "date" => [
                "type" => "date",
                "unix" => true,
            ],
            "attachment" => [
                "type" => "file",
                "extension" => ["png", "jpeg", "jpg", "gif", "pdf", "csf", "docx"],
                "max-size" => 1024 * 1024 * 5,
                "optional" => true,
                "obj" => true,
            ],
        ]);

        if (!$this->transactionManager->canOverPay($transaction) and $inputs['price'] > $transaction->remainPriceForAddPay()) {
            throw new InputValidationException('price');
        }

        $canAcceptPay = Authorization::is_accessed("transactions_pay_accept", 'financial');

        if (!$canAcceptPay and $inputs["date"] <= Date::time() - (86400 * 30)) {
            throw new InputValidationException("date");
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
            "method" => $paymentMethod->getName(),
            "price" => $inputs["price"],
            "status" => ($canAcceptPay ? TransactionPay::accepted : TransactionPay::pending),
            "currency" => $transaction->currency->id,
            "params" => $params,
        ));

        if ($pay) {
            if (Authentication::check()) {
                $log = new Log();
                $log->user = Authentication::getID();
                $log->type = PayLog::class;
                $log->title = t("financial.logs.transaction.pay", ["transaction_id" => $transaction->id]);
                $log->parameters = array(
                    'pay' => TransactionPay::byId($pay),
                    'currency' => $transaction->currency,
                );
                $log->save();
            }
            $parameter = [];
            if ($token = Http::getURIData("token")) {
                $parameter["token"] = $token;
            }
            $this->response->setStatus(true);
            $url = ($transaction->remainPriceForAddPay() > 0 ? 'transactions/pay/banktransfer/' : 'transactions/view/').$transaction->id;
            $this->response->Go(url($url, $parameter));
        }

        return $this->response;
    }
}
