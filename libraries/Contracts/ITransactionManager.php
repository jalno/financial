<?php

namespace packages\financial\Contracts;

use packages\financial\Transaction;

interface ITransactionManager
{
    /**
     * @throws Exception when can not find transaction
     */
    public function getByID(int $id): Transaction;

    public function canOnlinePay(int $id): bool;

    /**
     * @return \packages\transaction\Payport[]
     */
    public function getOnlinePayports(int $id): array;
}
