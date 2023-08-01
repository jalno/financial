<?php

namespace packages\financial;

use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\ITransactionManager;

class FinancialService implements IFinancialService
{
    private ?ITransactionManager $transactionManager = null;

    public function getTransactionManager(): ITransactionManager
    {
        if (!$this->transactionManager) {
            $this->transactionManager = new TransactionManager($this);
        }

        return $this->transactionManager;
    }
}
