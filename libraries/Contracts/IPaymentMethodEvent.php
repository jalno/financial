<?php

namespace packages\financial\Contracts;

use packages\base\EventInterface;

interface IPaymentMethodEvent extends EventInterface
{
    public function add(IPaymentMethod $paymentMethod);

    /**
     * @return IPaymentMethod[]
     */
    public function get(): array;

    public function setTransactionId(int $id);

    public function getTransactionId(): int;

    public function getTransactionManager(): ITransactionManager;
}
