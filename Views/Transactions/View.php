<?php

namespace packages\financial\Views\Transactions;

use packages\base\View\Error;
use packages\base\Views\Traits\Form;
use packages\financial\Authorization;
use packages\financial\Transaction;

class View extends \packages\financial\View
{
    use Form;

    protected $canPayAccept;
    protected $canPayReject;
    protected $canAcceptRefund;
    protected $canReimburse;

    public function __construct()
    {
        $this->canPayAccept = $this->canPayReject = Authorization::is_accessed('transactions_pay_accept');
        $this->canAcceptRefund = Authorization::is_accessed('transactions_refund_accept');
        $this->canReimburse = Authorization::is_accessed('transactions_reimburse');
    }

    public function settransactionData($data)
    {
        $this->setData($data, 'user');
    }

    public function getUserData($key)
    {
        return $this->data['user']->$key;
    }

    public function setTransaction(Transaction $transaction)
    {
        $this->setData($transaction, 'transaction');
        if (Transaction::paid == $transaction->status and !$transaction->isConfigured()) {
            $error = new Error();
            $error->setType(Error::WARNING);
            $error->setCode('financial.productNeedToConfigured');
            $this->addError($error);
        }
    }

    protected function getTransaction()
    {
        return $this->getData('transaction');
    }
}
