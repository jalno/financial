<?php

namespace themes\clipone\Views\Transactions;

use packages\financial\TransactionPay;
use packages\financial\Views\Transactions\Accept as TransactionsAccept;
use themes\clipone\Navigation;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Accept extends TransactionsAccept
{
    use ListTrait;
    use ViewTrait;

    /** @var packages\financial\Transaction */
    protected $transaction;

    public function __beforeLoad(): void
    {
        $this->transaction = $this->getTransactionData();
        $this->setTitle([
            t('transactions'),
            t('financial.transaction.accept'),
        ]);
        $this->addBodyClass('transaction-accept');
        Navigation::active('transactions/list');
    }

    protected function getPendingPaysCount(): int
    {
        return (int) (new TransactionPay())
        ->where('transaction', $this->transaction->id)
        ->where('status', TransactionPay::PENDING)
        ->count();
    }
}
