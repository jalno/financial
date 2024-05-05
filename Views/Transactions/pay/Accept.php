<?php

namespace packages\financial\Views\Transactions\Pay;

use packages\financial\TransactionPay;
use packages\financial\Views\Form;

class Accept extends Form
{
    public function setPay(TransactionPay $pay)
    {
        $this->setData($pay, 'pay');
    }

    public function getPay()
    {
        return $this->getData('pay');
    }
}
