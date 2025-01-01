<?php

namespace packages\financial\controllers\PaymentMethods;

use packages\base\InputValidationException;
use packages\base\NotFound;
use packages\base\Response;
use packages\base\View;
use packages\financial\controllers\Transactions;
use packages\financial\Currency;
use packages\financial\PaymentMethdos\OnlinePaymentMethod;
use packages\financial\payport\Redirect;
use packages\financial\TransactionManager;
use packages\financial\views\transactions\pay\onlinepay\redirect as OnlinepayRedirect;
use packages\userpanel\Controller;
use themes\clipone\views\financial\PaymentMethods\OnlinePayView;

/**
 * @property Response $response
 */
class OnlineController extends Controller
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
        $paymentMethod = OnlinePaymentMethod::getInstance();

        if (
            !$this->transactionManager->canPay($transaction) or
            !$paymentMethod->canPay($transaction)
        ) {
            throw new NotFound();
        }

        /**
         * @var OnlinePayView
         */
        $view = View::byName(OnlinePayView::class);
        $view->transaction = $transaction;
        $view->payports = $paymentMethod->getPayports($transaction);

        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function pay(array $data): Response
    {
        $transaction = Transactions::getTransaction($data['id']);
        $paymentMethod = OnlinePaymentMethod::getInstance();

        if (
            !$this->transactionManager->canPay($transaction) or
            !$paymentMethod->canPay($transaction)
        ) {
            throw new NotFound();
        }

        $payports = $paymentMethod->getPayports($transaction);
        /**
         * @var OnlinePayView
         */
        $view = View::byName(OnlinePayView::class);
        $view->transaction = $transaction;
        $view->payports = $payports;

        $rules = [
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
            'currency' => array(
                'type' => Currency::class,
                'optional' => true,
                'default' => $transaction->currency,
            ),
        ];

        $view->setDataForm($this->inputsvalue($rules));
        $this->response->setView($view);

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

        switch ($redirect->method) {
            case Redirect::get:
                $this->response->Go($redirect->getURL());
                break;

            case Redirect::post:
                $view = View::byName(OnlinepayRedirect::class);
                $view->setTransaction($transaction);
                $view->setRedirect($redirect);
                $this->response->setView($view);
                break;
        }

        $this->response->setStatus(true);

        return $this->response;
    }
}
