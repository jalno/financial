<?php

namespace packages\financial\Contracts;

interface IPaymentMethodManager
{
    /**
     * @return IPaymentMethod[]
     */
    public function all(int $transactionId): array;

    public function canPayBy(string $name, int $transactionId): bool;
}
