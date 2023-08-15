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
     *
     * @throws \Exceotion if not allowed to pay by online payports
     */
    public function getOnlinePayports(int $id): array;

    public function canPayByTransferBank(int $id): bool;

    /**
     * @return \packages\transaction\Bank\Account[]
     *
     * @throws \Exception if not allowed to pay by bank transfer method
     */
    public function getBankAccountsForTransferPay(int $id): array;

    public function canPayByCredit(int $id, ?int $opratorID): bool;

    /**
     * @return string[]
     */
    public function getAvailablePaymentMethods(int $id, ?int $opratorID): array;

    /**
     * @return string[]
     */
    public function getPaymentMethods(int $id): array;

    /**
     * @param array{user:int,title:string,currency:int,create_at?:int,expire?:int,params?:array<string,mixed>,products:array{title:string,price:float,method:int,type?:string,description?:string,discount?:float,vat?:float,number?:positive-int,currency?:int,params?:array<string,mixed>}} $data
     */
    public function store(array $data, ?int $operatorID, bool $sendNotification): Transaction;
}
