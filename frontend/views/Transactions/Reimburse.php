<?php

namespace themes\clipone\Views\Transactions;

use packages\financial\Currency;
use packages\financial\Transaction;
use packages\financial\TransactionPay;
use packages\financial\Views\Transactions\Reimburse as TransactionReimburse;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\Views\TransactionTrait;
use themes\clipone\ViewTrait;

class Reimburse extends TransactionReimburse
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;
    use TransactionTrait;

    /** @var Transaction|null */
    protected $transaction;

    /** @var TransactionPay[] */
    protected $pays = [];

    /** @var Curreny|null */
    protected $userDefaultCurrency;

    /** @var int[] */
    protected $notRefundablePays = [];

    public function __beforeLoad(): void
    {
        $this->transaction = $this->getTransaction();
        $this->pays = $this->getPays();
        $this->userDefaultCurrency = Currency::getDefault($this->transaction->user);
        Navigation::active('transactions/list');

        $this->setTitle(t('packages.financial.reimburse.title'));
        $this->setShortDescription(t('transaction.number', [
            'number' => $this->transaction->id]
        ));

        $this->addBodyClass('transactions');
        $this->addBodyClass('transaction-reimburse');
    }

    protected function getPaysTotalAmountByCurrency(): int
    {
        $currency = $this->userDefaultCurrency;

        return array_reduce($this->getPays(), function ($carry, TransactionPay $pay) use (&$currency) {
            $price = 0;
            try {
                $price = $pay->currency->changeTo($pay->price, $currency);
            } catch (Currency\UnChangableException $e) {
                $this->notRefundablePays[] = $pay->id;
            }

            return $carry + $price;
        }, 0);
    }
}
