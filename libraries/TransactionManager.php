<?php

namespace packages\financial;

use packages\base\Date;
use packages\base\DB;
use packages\base\Exception;
use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\IPaymentMethod;
use packages\financial\Contracts\ITransactionManager;
use packages\financial\events\transactions as Events;
use packages\financial\logs\transactions as Logs;
use packages\financial\PaymentMethdos\BankTransferPaymentMethod;
use packages\financial\PaymentMethdos\CreditPaymentMethod;
use packages\financial\PaymentMethdos\OnlinePaymentMethod;
use packages\userpanel\Log;
use packages\userpanel\User;

class TransactionManager implements ITransactionManager
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;

    public IFinancialService $serviceProvider;

    public function __construct(?IFinancialService $serviceProvider = null)
    {
        $this->serviceProvider = $serviceProvider ?: FinancialService::getInstance();
    }

    public function getByID(int $id): Transaction
    {
        $transaction = (new Transaction())->byId($id);

        if (!$transaction) {
            throw new \Exception('Can not find any transaction with id: '.$id);
        }

        return $transaction;
    }

    public function canOnlinePay(int|Transaction $id): bool
    {
        return OnlinePaymentMethod::getInstance()->canPay($id);
    }

    /**
     * @return Payport[]
     */
    public function getOnlinePayports(int|Transaction $id): array
    {
        return OnlinePaymentMethod::getInstance()->getPayports($id);
    }

    public function canPayByTransferBank(int|Transaction $id): bool
    {
        return BankTransferPaymentMethod::getInstance()->canPay($id);
    }

    public function getBankAccountsForTransferPay(int|Transaction $id): array
    {
        return BankTransferPaymentMethod::getInstance()->getBankAccountsForPay($id);
    }

    public function canPayByCredit(int|Transaction $id): bool
    {
        return CreditPaymentMethod::getInstance()->canPay($id);
    }

    public function getAvailablePaymentMethods(int|Transaction $id): array
    {
        $transaction = $id instanceof Transaction ? $id : $this->getByID($id);

        $filterMethods = $transaction->param('available_payment_methods') ?: [];
        return array_filter(
            $this->getPaymentMethods($transaction),
            fn (IPaymentMethod $paymentMethod) => (
                (empty($filterMethods) or in_array($paymentMethod->getName(), $filterMethods)) and
                $paymentMethod->canPay($transaction)
            )
        );
    }

    public function getPaymentMethods(int|Transaction $id): array
    {
        return $this->serviceProvider->getPaymentMethodManager()->all($id);
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

    public function getForPayById(int $id): Transaction
    {
        $transaction = $this->getByID($id);
        if (!$this->canPay($transaction)) {
            throw new \Exception('Transaction '.$id.' is not payble. Please contact support');
        }

        return $transaction;
    }

    public function canPay(int|Transaction $transaction): bool
    {
        if (!($transaction instanceof Transaction)) {
            $transaction = $this->getByID($transaction);
        }

        return $transaction->canAddPay() and
            !$transaction->param('UnChangableException');
    }

    public function canOverPay(int|Transaction $transaction): bool
    {
        if (!($transaction instanceof Transaction)) {
            $transaction = $this->getByID($transaction);
        }

        return !((new Transaction_Product())
            ->where('transaction', $transaction->id)
            ->where('type', [products\AddingCredit::class, "\\" . products\AddingCredit::class], 'in')
		    ->has());
    }
}
