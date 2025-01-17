<?php

namespace packages\financial;

use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\IPaymentMethodManager;
use packages\financial\Contracts\ITransactionManager;

class FinancialService implements IFinancialService
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;

    private ?ITransactionManager $transactionManager = null;

    public function getTransactionManager(): ITransactionManager
    {
        if (!$this->transactionManager) {
            $this->transactionManager = new TransactionManager($this);
        }

        return $this->transactionManager;
    }

    public function getPaymentMethodManager(): IPaymentMethodManager
    {
        return PaymentMethodManager::getInstance($this);
    }
}
