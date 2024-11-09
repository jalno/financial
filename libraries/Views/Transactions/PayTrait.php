<?php

namespace packages\financial\Views\Transactions;

use packages\financial\Authorization;
use packages\financial\Transaction;

trait PayTrait
{
    public function setTransaction(Transaction $transaction)
    {
        $this->setData($transaction, 'transaction');
    }

    public function getTransaction()
    {
        return $this->getData('transaction');
    }
}
