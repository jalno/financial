<?php

namespace packages\financial\PaymentMethdos;

use packages\financial\Contracts\IPaymentMethod;
use packages\financial\Transaction;
use packages\financial\Transaction_pay as TransactionPay;
use packages\financial\TransactionManager;
use packages\userpanel\Authorization;

class CreditPaymentMethod implements IPaymentMethod
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;

    public function getName(): string
    {
        return 'credit';
    }

    public function getIcon(): string
    {
        return 'fa fa-vcard-o';
    }

    public function canPay(int|Transaction $transactionId): bool
    {
        $transaction = $transactionId instanceof $transactionId ?
            $transactionId :
            TransactionManager::getInstance()->getForPayById($transactionId);

        if (
            !$transaction->user or
            (
                $transaction->user->credit <= 0 and
                !Authorization::is_accessed('payment_method_credit_debt', 'financial')
            )
        ) {
            return false;
        }

        return $transaction->canPayByCredit();
    }

    public function getPayTitle(TransactionPay $pay): string
    {
        return t("pay.method.credit");
    }
}
