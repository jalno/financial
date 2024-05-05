<?php

namespace packages\financial\Views\Transactions\Pay;

use packages\financial\Views\Form;
use packages\financial\Views\Transactions\PayTrait;

class BankTransfer extends Form
{
    use PayTrait;

    public function setBankAccounts($bankaccounts)
    {
        $this->setData($bankaccounts, 'bankaccounts');
    }

    public function getBankAccounts()
    {
        return $this->getData('bankaccounts');
    }

    public function setBanktransferPays($transaction_pays)
    {
        $this->setData($transaction_pays, 'transaction_pays');
    }

    public function getBanktransferPays()
    {
        return $this->getData('transaction_pays');
    }
}
