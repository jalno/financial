<?php

namespace packages\financial;

use packages\financial\Contracts\IPaymentMethodManager;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\events\PaymentMethodEvent;

class PaymentMethodManager implements IPaymentMethodManager
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;
    
    private ITransactionManager $transactionManager;

    public function __construct(?ITransactionManager $transactionManager = null)
    {
        $this->transactionManager = $transactionManager ?: TransactionManager::getInstance();
    }

    public function all(int|Transaction $transactionId): array
    {
        return (new PaymentMethodEvent($transactionId, $this->transactionManager))->get();
    }

    public function canPayBy(string $name, int $transactionId): bool
    {
        return in_array($name, $this->all($transactionId));
    }
}
