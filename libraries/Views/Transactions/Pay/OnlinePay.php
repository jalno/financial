<?php

namespace packages\financial\Views\Transactions\Pay;

use packages\financial\Transaction;
use packages\financial\Views\Form;

class OnlinePay extends Form
{
    public function setTransaction(Transaction $transaction): void
    {
        $this->setData($transaction, 'transaction');
    }

    public function getTransaction(): Transaction
    {
        return $this->getData('transaction');
    }

    public function setPayports($payports): void
    {
        $this->setData($payports, 'payports');
    }

    public function getPayports(): array
    {
        return $this->getData('payports') ?? [];
    }

    public function export(): array
    {
        return [
            'data' => [
                'payports' => array_map(function ($payport) {
                    return [
                        'id' => $payport->id,
                        'title' => $payport->title,
                    ];
                }, $this->getPayports()),
                'payablePrice' => $this->getTransaction()->remainPriceForAddPay(),
                'currency' => $this->getTransaction()->currency->toArray(false),
            ],
        ];
    }
}
