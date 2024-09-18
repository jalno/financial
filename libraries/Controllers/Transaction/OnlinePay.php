<?php

namespace packages\financial\Controllers\Transaction;

use packages\base\Date;
use packages\base\Http;
use packages\base\NotFound;
use packages\base\Response;
use packages\base\Translator;
use packages\base\View;
use packages\financial\Authentication;
use packages\financial\Logs;
use packages\financial\PayPort\GateWayException;
use packages\financial\PayPort\VerificationException;
use packages\financial\PayPortPay;
use packages\financial\Transaction;
use packages\financial\TransactionPay;
use packages\financial\Views;
use packages\userpanel\Controller;
use packages\userpanel\Log;

class OnlinePay extends Controller
{
    public function __construct()
    {
        if (!Authentication::check()) {
            $token = HTTP::getURIData('token');
            if ($token) {
                $isValidToken = (new Transaction())->where('token', $token)->has();
                if (!$isValidToken) {
                    parent::response(Authentication::FailResponse());
                }
            } else {
                parent::response(Authentication::FailResponse());
            }
        }
        parent::__construct();
    }

    public function callBack($data): Response
    {
        $pay = PayPortPay::byId($data['pay']);
        if (!$pay or PayPortPay::pending != $pay->status) {
            throw new NotFound();
        }
        $this->response->setStatus(false);
        $view = View::byName(Views\Transactions\Pay\OnlinePay\Error::class);
        $this->response->setView($view);
        $view->setPay($pay);
        try {
            if (PayPortPay::success != $pay->verification()) {
                throw new VerificationException();
            }
            $this->response->setStatus(true);
            $transaction = $pay->transaction;
            $tPay = $transaction->addPay([
                'date' => Date::time(),
                'method' => TransactionPay::onlinepay,
                'price' => $pay->price,
                'currency' => $pay->currency,
                'status' => TransactionPay::accepted,
                'params' => [
                    'payport_pay' => $pay->id,
                ],
            ]);
            if ($transaction->user) {
                $log = new Log();
                $log->user = $transaction->user;
                $log->type = Logs\Transactions\Pay::class;
                $log->title = t('financial.logs.transaction.pay', ['transaction_id' => $pay->transaction->id]);
                $parameters['pay'] = TransactionPay::byId($tPay);
                $parameters['currency'] = $pay->currency;
                $log->parameters = $parameters;
                $log->save();
            }
            $this->response->setStatus(true);
        } catch (GateWayException $e) {
            $view->setError('gateway');
        } catch (VerificationException $e) {
            $view->setError('verification', $e->getMessage());
        }

        return $this->response;
    }
}
