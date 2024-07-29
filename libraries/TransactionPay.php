<?php

namespace packages\financial;

use packages\base\DB;
use packages\base\DB\DBObject;
use packages\financial\Bank\Account;
use packages\financial\Transaction;
use packages\financial\TransactionPayParam;

class TransactionPay extends DBObject
{
    /** status */
    public const PENDING = self::pending;
    public const ACCEPTED = self::accepted;
    public const REJECTED = self::rejected;
    public const REIMBURSE = 3;

    /** method */
    public const CREDIT = self::credit;
    public const BANKTRANSFER = self::banktransfer;
    public const ONLINEPAY = self::onlinepay;
    public const PAYACCEPTED = self::payaccepted;

    /* old style const, we dont removed these for backward compatibility */
    public const pending = 2;
    public const accepted = 1;
    public const rejected = 0;

    public const credit = 1;
    public const banktransfer = 2;
    public const onlinepay = 3;
    public const payaccepted = 4;

    protected $dbTable = 'financial_transactions_pays';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'transaction' => ['type' => 'int', 'required' => true],
        'method' => ['type' => 'int', 'required' => true],
        'date' => ['type' => 'int', 'required' => true],
        'price' => ['type' => 'double', 'required' => true],
        'currency' => ['type' => 'int', 'required' => true],
        'status' => ['type' => 'int', 'required' => true],
    ];
    protected $relations = [
        'transaction' => ['hasOne', Transaction::class, 'transaction'],
        'params' => ['hasMany', TransactionPayParam::class, 'pay'],
        'currency' => ['hasOne', Currency::class, 'currency'],
    ];

    public function __construct($data = null, $connection = 'default')
    {
        $data = $this->processData($data);
        parent::__construct($data, $connection);
    }
    protected $tmparams = [];

    private function processData($data)
    {
        $newdata = [];
        if (is_array($data)) {
            if (isset($data['params'])) {
                foreach ($data['params'] as $name => $value) {
                    $this->tmparams[$name] = new TransactionPayParam([
                        'name' => $name,
                        'value' => $value,
                    ]);
                }
                unset($data['params']);
            }
            $newdata = $data;
        }
        if (!isset($data['date'])) {
            $newdata['date'] = time();
        }

        if (!isset($data['status'])) {
            $newdata['status'] = self::accepted;
        }

        return $newdata;
    }

    public function setParam($name, $value)
    {
        $param = false;
        foreach ($this->params as $p) {
            if ($p->name == $name) {
                $param = $p;
                break;
            }
        }
        if (!$param) {
            $param = new TransactionPayParam([
                'name' => $name,
                'value' => $value,
            ]);
        } else {
            $param->value = $value;
        }

        if (!$this->id) {
            $this->tmparams[$name] = $param;
        } else {
            $param->pay = $this->id;

            return $param->save();
        }
    }

    public function save($data = null)
    {
        $return = parent::save($data);
        if ($return) {
            foreach ($this->tmparams as $param) {
                $param->pay = $this->id;
                $param->save();
            }
            $this->tmparams = [];
            if (in_array($this->transaction->status, [Transaction::PENDING, Transaction::UNPAID])) {
                if (0 == $this->transaction->payablePrice()) {
                    $this->transaction->status = Transaction::PAID;
                    $this->transaction->expire_at = null;
                    $this->transaction->paid_at = time();
                    $this->transaction->afterPay();
                    $this->transaction->save();
                    $event = new Events\Transactions\Pay($this->transaction);
                    $event->trigger();
                    if ($this->transaction->isConfigured()) {
                        $this->transaction->trigger_paid();
                    }
                } elseif (self::BANKTRANSFER == $this->method and self::PENDING == $this->status and Transaction::PENDING != $this->transaction->status) {
                    $this->transaction->status = Transaction::PENDING;
                    $this->transaction->save();
                } elseif (Transaction::PENDING == $this->transaction->status) {
                    $hasPendingPay = (new self())->where('transaction', $this->transaction->id)
                                    ->where('status', self::PENDING)
                                    ->has();
                    if (!$hasPendingPay) {
                        $this->transaction->status = Transaction::UNPAID;
                        $this->transaction->save();
                    }
                }
            }
        }

        return $return;
    }

    public function param($name)
    {
        if (!$this->id) {
            return isset($this->tmparams[$name]) ? $this->tmparams[$name]->value : null;
        } else {
            foreach ($this->params as $param) {
                if ($param->name == $name) {
                    return $param->value;
                }
            }

            return false;
        }
    }

    public function deleteParam(string $name)
    {
        if ($this->isNew) {
            unset($this->tmparams[$name]);
        } else {
            DB::where('pay', $this->id)
                ->where('name', $name)
                ->delete('financial_transactions_pays_params');
        }
    }

    public function getBanktransferBankAccount(): ?Account
    {
        if (self::banktransfer != $this->method) {
            return null;
        }
        $bankaccount_id = $this->param('bankaccount');

        return $bankaccount_id ? (new Account())->byID($bankaccount_id) : null;
    }

    public function delete()
    {
        $transaction = $this->transaction;
        $return = parent::delete();
        if ($return) {
            $hasPendingPay = (new self())->where('transaction', $transaction->id)
                            ->where('status', self::PENDING)
                            ->has();
            if ($hasPendingPay) {
                $transaction->status = Transaction::PENDING;
            } elseif ($transaction->payablePrice() > 0) {
                $transaction->status = Transaction::UNPAID;
            }
            $transaction->save();
        }

        return $return;
    }

    protected function convertPrice()
    {
        if ($this->currency->id == $this->transaction->currency->id) {
            return $this->price;
        }

        return $this->currency->changeTo($this->price, $this->transaction->currency);
    }
}
