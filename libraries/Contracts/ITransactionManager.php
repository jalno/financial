<?php

namespace packages\financial\Contracts;

use packages\financial\Transaction;

interface ITransactionManager
{
    /**
     * @throws Exception when can not find transaction
     */
    public function getByID(int $id): Transaction;

    /**
     * @throws Exception when can not find transaction ot transaction is not payable
     */
    public function getForPayById(int $id): Transaction;

    public function canPay(int|Transaction $id): bool;

    public function canOnlinePay(int|Transaction $id): bool;

    /**
     * @return \packages\transaction\Payport[]
     *
     * @throws \Exceotion if not allowed to pay by online payports
     */
    public function getOnlinePayports(int|Transaction $id): array;

    public function canPayByTransferBank(int|Transaction $id): bool;

    /**
     * @return \packages\transaction\Bank\Account[]
     *
     * @throws \Exception if not allowed to pay by bank transfer method
     */
    public function getBankAccountsForTransferPay(int|Transaction $id): array;

    public function canPayByCredit(int|Transaction $id): bool;

    /**
     * @return IPaymentMethod[]
     */
    public function getAvailablePaymentMethods(int|Transaction $id): array;

    /**
     * @return IPaymentMethod[]
     */
    public function getPaymentMethods(int|Transaction $id): array;

    /**
     * @param array{user:int,title:string,currency:int,create_at?:int,expire?:int,params?:array<string,mixed>,products:array{title:string,price:float,method:int,type?:string,description?:string,discount?:float,vat?:float,number?:positive-int,currency?:int,params?:array<string,mixed>}} $data
     */
    public function store(array $data, ?int $operatorID, bool $sendNotification): Transaction;

    public function delete(int $id, ?int $operatorID);

    /**
     * @param array{user?:int,title?:string,currency?:int,create_at?:int,expire?:int,params?:array<string,mixed>,products:array{id?:int,title?:string,price?:float,method?:int,type?:string,description?:string,discount?:float,vat?:float,number?:positive-int,currency?:int,params?:array<string,mixed>}} $data
     */
    public function update(int $id, array $data, ?int $operatorID): Transaction;
}
