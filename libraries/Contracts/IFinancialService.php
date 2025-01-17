<?php

namespace packages\financial\Contracts;

interface IFinancialService
{
    public function getTransactionManager(): ITransactionManager;

    public function getPaymentMethodManager(): IPaymentMethodManager;
}
