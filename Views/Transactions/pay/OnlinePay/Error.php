<?php

namespace packages\financial\Views\Transactions\Pay\OnlinePay;

use packages\financial\PayPortPay;
use packages\financial\View;

class Error extends View
{
    public function setPay(PayPortPay $pay)
    {
        $this->setData($pay, 'pay');
    }

    public function getPay()
    {
        return $this->getData('pay');
    }

    public function setError($error, $message = null)
    {
        $this->setData([
            'error' => $error,
            'message' => $message,
        ], 'error');
    }

    public function getError()
    {
        return $this->getData('error');
    }

    public function export()
    {
        return [
            'data' => [
                'error' => $this->getError(),
            ],
        ];
    }
}
