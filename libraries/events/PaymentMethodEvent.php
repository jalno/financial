<?php

namespace packages\financial\events;

use packages\base\Event;
use packages\financial\Contracts\IPaymentMethod;
use packages\financial\Contracts\IPaymentMethodEvent;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\Transaction;

class PaymentMethodEvent extends Event implements IPaymentMethodEvent
{
    /**
     * @var array<string,IPaymentMethod>
     */
    private array $methods = [];
    private ITransactionManager $transactionManager;
    private Transaction $transaction;

    public function __construct(int|Transaction $transaction, ?ITransactionManager $transactionManager = null)
    {
        $this->transactionManager = $transactionManager ?: FinancialService::getInstance()->getTransactionManager();
        if ($transaction instanceof Transaction) {
            $this->transaction = $transaction;
        } else {
            $this->setTransactionId($transaction);
        }
    }

    public function setTransactionId(int $id)
    {
        $transaction = $this->transactionManager->getForPayById($id);

        $this->transaction = $transaction;
    }

    public function getTransactionId(): int
    {
        return $this->transaction->id;
    }

    public function add(IPaymentMethod $paymentMethod)
    {
        $this->methods[$paymentMethod->getName()] = $paymentMethod;
    }

    public function get(): array
    {
        $this->trigger();

        return $this->methods;
    }

    public function getTransactionManager(): ITransactionManager
    {
        return $this->transactionManager;
    }
}
