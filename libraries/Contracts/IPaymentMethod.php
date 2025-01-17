<?php

namespace packages\financial\Contracts;

use packages\financial\Transaction;
use packages\financial\Transaction_pay as TransactionPay;

interface IPaymentMethod
{
    public function getName(): string;
    
    public function getIcon(): string;

    public function canPay(int|Transaction $transactionId): bool;

    public function getPayTitle(TransactionPay $pay): string;
}
