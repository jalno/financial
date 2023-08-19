<?php

namespace packages\financial;

use packages\base\Date;
use packages\base\DB;
use packages\base\DB\Parenthesis;
use packages\base\Exception;
use packages\base\Options;
use packages\financial\Bank\Account;
use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\events\transactions as Events;
use packages\financial\logs\transactions as Logs;
use packages\userpanel\Log;
use packages\userpanel\User;

class TransactionManager implements ITransactionManager
{
    public IFinancialService $serviceProvider;

    public function __construct(IFinancialService $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function getByID(int $id): Transaction
    {
        $transaction = (new Transaction())->byId($id);

        if (!$transaction) {
            throw new \Exception('Can not find any transaction with id: '.$id);
        }

        return $transaction;
    }

    public function canOnlinePay(int $id): bool
    {
        return in_array(Transaction::ONLINE_PAYMENT_METHOD, $this->getPaymentMethods($id)) and
            !empty($this->getOnlinePayports($id));
    }

    /**
     * @return Payport[]
     */
    public function getOnlinePayports(int $id): array
    {
        if (!in_array(Transaction::ONLINE_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            throw new \Exception('Can not pay with online payports');
        }

        $transaction = $this->getByID($id);

        $payportIDs = $transaction->param('available_online_payports');

        if (!$payportIDs) {
            $payportIDs = Options::get('packages.financial.available_online_payports');
        }

        $currency = $transaction->currency;

        DB::join('financial_payports_currencies', 'financial_payports_currencies.payport=financial_payports.id', 'INNER');
        DB::join('financial_currencies', 'financial_currencies.id=financial_payports_currencies.currency', 'LEFT');
        DB::join('financial_currencies_rates', 'financial_currencies_rates.currency=financial_currencies.id', 'LEFT');

        $query = new Payport();

        $parenthesis = new Parenthesis();
        $parenthesis->where('financial_payports_currencies.currency', $currency->id);
        $parenthesis->orWhere('financial_currencies_rates.changeTo', $currency->id);

        $query->where($parenthesis);

        $query->where('financial_payports.status', Payport::active);

        if ($payportIDs) {
            $query->where('financial_payports.id', $payportIDs, 'IN');
        }

        $query->setQueryOption('DISTINCT');
        $payports = $query->get(null, 'financial_payports.*');

        return $payports;
    }

    public function canPayByTransferBank(int $id): bool
    {
        return in_array(Transaction::BANK_TRANSFER_PAYMENT_METHOD, $this->getPaymentMethods($id)) and
            !empty($this->getBankAccountsForTransferPay($id));
    }

    public function getBankAccountsForTransferPay(int $id): array
    {
        if (!in_array(Transaction::BANK_TRANSFER_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            throw new \Exception('Can not pay with bank transfer');
        }

        $transaction = $this->getByID($id);

        $bankAccountIDs = $transaction->param('available_bank_accounts');

        if (!$bankAccountIDs) {
            $bankAccountIDs = Options::get('packages.financial.pay.tansactions.banka.accounts');
        }

        $query = new Account();
        $query->with('bank');
        $query->where('financial_banks_accounts.status', Account::Active);
        if ($bankAccountIDs) {
            $query->where('financial_banks_accounts.id', $bankAccountIDs, 'IN');
        }

        return $query->get();
    }

    public function canPayByCredit(int $id, ?int $operatorID = null): bool
    {
        if (!in_array(Transaction::CREDIT_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            return false;
        }

        $transaction = $this->getByID($id);

        $operator = null;
        if ($operatorID) {
            $operator = (new User())->byId($operatorID);

            if (!$operator) {
                throw new \Exception('Can not fin operator by id: '.$operatorID);
            }
        }

        if ((!$operator or $operator->credit <= 0) and $transaction->user->credit <= 0) {
            return false;
        }

        return $transaction->canPayByCredit();
    }

    public function getAvailablePaymentMethods(int $id, ?int $operatorID = null): array
    {
        return array_filter($this->getPaymentMethods($id), function (string $method) use ($id, $operatorID) {
            switch ($method) {
                case Transaction::CREDIT_PAYMENT_METHOD:
                    return $this->canPayByCredit($id, $operatorID);
                case Transaction::BANK_TRANSFER_PAYMENT_METHOD:
                    return $this->canPayByTransferBank($id);
                case Transaction::ONLINE_PAYMENT_METHOD:
                    return $this->canOnlinePay($id);
                default:
                    return false;
            }
        });
    }

    public function getPaymentMethods($id): array
    {
        $transaction = $this->getByID($id);

        $methods = $transaction->param('available_payment_methods');

        if (!is_array($methods)) {
            $methods = [
                Transaction::CREDIT_PAYMENT_METHOD,
                Transaction::BANK_TRANSFER_PAYMENT_METHOD,
                Transaction::ONLINE_PAYMENT_METHOD,
            ];
        }

        return $methods;
    }

    public function store(array $data, ?int $operatorID = null, bool $sendNotification = true): Transaction
    {
        $transactionFields = ['title', 'products'];
        foreach ($transactionFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException($field);
            }
        }

        if (!isset($data['currency'])) {
            $user = isset($data['user']) ? User::byId($data['user']) : null;
            $data['currency'] = Currency::getDefault($user);
        }

        $productFields = ['title', 'price', 'method'];
        foreach ($data['products'] as $key => $item) {
            foreach ($productFields as $field) {
                if (!isset($item[$field])) {
                    throw new \InvalidArgumentException("products[{$key}][{$field}]");
                }
            }
        }

        try {
            DB::startTransaction();

            $model = new Transaction();

            $transactionFields[] = 'user';
            $transactionFields[] = 'create_at';
            $transactionFields[] = 'expire_at';
            if (!isset($data['create_at'])) {
                $data['create_at'] = Date::time();
            }

            foreach ($transactionFields as $field) {
                $model->{$field} = $data[$field] ?? null;
            }

            $transactionID = $model->save();

            if (isset($data['params'])) {
                foreach ($data['params'] as $name => $value) {
                    $paramID = $model->setParam($name, $value);

                    if (!$paramID) {
                        throw new \Exception('Can not save transaction param');
                    }
                }
            }

            if (!$transactionID) {
                throw new Exception('Can not store transaction');
            }

            foreach ($data['products'] as $product) {
                foreach (['discount', 'vat'] as $item) {
                    if (!isset($product[$item])) {
                        $product[$item] = 0;
                    }
                }

                if (!isset($product['number']) or $product['number'] < 1) {
                    $product['number'] = 1;
                }

                if (!isset($product['currency'])) {
                    $product['currency'] = $data['currency'];
                }

                $productID = $model->addProduct($product);

                if (!$productID) {
                    throw new Exception('Can not store transction product');
                }
            }

            $model->price = $model->totalPrice();
            $model->save();

            if ($operatorID) {
                $log = new Log();
                $log->user = $operatorID;
                $log->type = Logs\Add::class;
                $log->title = t('financial.logs.transaction.add', ['transaction_id' => $model->id]);
                $log->parameters = [];
                $log->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            throw $e;
        }

        if ($sendNotification) {
            (new Events\Add($model))->trigger();
        }

        return $model;
    }

    public function delete(int $id, ?int $operatorID = null): Transaction
    {
        $transaction = $this->getByID($id);

        $data = $transaction->toArray(false);
        $result = $transaction->delete();

        if (!$result) {
            throw new \Exception('Can not delete transaction');
        }

        $log = new Log();
        $log->user = $operatorID;
        $log->type = Logs\Delete::class;
        $log->title = t('financial.logs.transaction.delete', ['transaction_id' => $id]);
        $log->parameters = ['transaction' => $data];
        $log->save();

        return $transaction;
    }

    public function update(int $id, array $data, ?int $operatorID = null): Transaction
    {
        $transaction = $this->getByID($id);

        $changes = [
            'newData' => [],
            'oldData' => [],
        ];

        foreach (['title', 'create_at', 'expire_at'] as $item) {
            if (isset($data[$item]) and $transaction->{$item} != $data[$item]) {
                $changes['oldData'][$item] = $transaction->{$item};
                $changes['newData'][$item] = $data[$item];

                $transaction->{$item} = $data[$item];
            }
        }

        foreach (['currency', 'user'] as $item) {
            if (isset($data[$item]) and $transaction->{$item}->id != $data[$item]) {
                $changes['oldData'][$item] = $transaction->{$item}->id;
                $changes['newData'][$item] = $data[$item];

                $transaction->{$item} = $data[$item];
            }
        }

        if ($changes['newData'] or $changes['oldData']) {
            $result = $transaction->save();

            if (!$result) {
                throw new \Exception('Can not update transacton');
            }
        }

        if (isset($data['params'])) {
            $paramChanges = [
                'new' => [],
                'old' => [],
            ];

            foreach ($data['params'] as $key => $value) {
                $param = $transaction->param($key);
                if (!$param) {
                    $paramChanges['new'][$key] = $value;
                } elseif ($param != $value) {
                    $paramChanges['new'][$key] = $value;
                    $paramChanges['old'][$key] = $value;
                }

                $transaction->setParam($key, $value);
            }

            if ($paramChanges['new']) {
                $changes['newData']['params'] = $paramChanges['new'];
            }
            if ($paramChanges['old']) {
                $changes['oldData']['params'] = $paramChanges['old'];
            }
        }

        if (isset($data['products'])) {
            $productsChanges = [
                'new' => [],
                'old' => [],
            ];

            foreach ($data['products'] as $product) {
                if (isset($product['id'])) {
                    $query = new Transaction_product();
                    $query->where('id', $product['id']);
                    $query->where('transaction', $id);

                    $model = $query->getOne();
                    if (!$model) {
                        throw new \Exception('Can not find transaction product with id :'.$product['id']);
                    }

                    $productChanges = [
                        'new' => [],
                        'old' => [],
                    ];

                    foreach (['title', 'number', 'price', 'discount', 'vat', 'description'] as $item) {
                        if (isset($product[$item]) and $model->{$item} != $product[$item]) {
                            $productChanges['new'][$item] = $product[$item] ?: '-';
                            $productChanges['old'][$item] = $model->{$item};

                            $model->{$item} = $product[$item];
                        }
                    }

                    if (isset($product['currency']) and $model->currency->id != $product['currency']) {
                        $productChanges['new']['currency'] = $product['currency'];
                        $productChanges['old']['currency'] = $model->currency->id;

                        $model->currency = $product['currency'];
                    }

                    if ($productChanges['new'] or $productChanges['old']) {
                        $result = $model->save();

                        if (!$result) {
                            throw new Exception('Can not update transaction product');
                        }

                        if ($productChanges['new']) {
                            $productsChanges['new'][$model->id] = $productChanges['new'];
                        }
                        if ($productChanges['old']) {
                            $productsChanges['old'][$model->id] = $productChanges['old'];
                        }
                    }
                } else {
                    foreach (['discount', 'vat'] as $item) {
                        if (!isset($product[$item])) {
                            $product[$item] = 0;
                        }
                    }

                    if (!isset($product['number']) or $product['number'] < 1) {
                        $product['number'] = 1;
                    }

                    if (!isset($product['currency'])) {
                        $product['currency'] = $data['currency'] ?? $transaction->currency->id;
                    }

                    $productID = $transaction->addProduct($product);

                    if (!$productID) {
                        throw new Exception('Can not store transction product');
                    }

                    $query = new Transaction_product();
                    $query->where('id', $productID);
                    $query->ArrayBuilder();

                    $product = $query->getOne();

                    $productsChanges['new'][$productID] = $product;
                }
            }

            if ($productsChanges['new']) {
                $changes['newData']['products'] = $productsChanges['new'];
            }
            if ($productsChanges['old']) {
                $changes['oldData']['products'] = $productsChanges['old'];
            }
        }

        if ($changes['newData'] or $changes['oldData']) {
            $log = new log();
            $log->user = $operatorID;
            $log->type = Logs\edit::class;
            $log->title = t('financial.logs.transaction.edit', ['transaction_id' => $transaction->id]);
            $log->parameters = $changes;
            $log->save();

            $event = new Events\Edit($transaction);
            $event->trigger();
        }

        return $transaction;
    }
}
