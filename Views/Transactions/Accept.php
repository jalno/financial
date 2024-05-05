<?php

namespace packages\financial\Views\Transactions;

use packages\financial\Transaction;

class Accept extends \packages\financial\View
{
    protected $transaction;

    public function setTransactionData(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransactionData()
    {
        return $this->transaction;
    }
}
