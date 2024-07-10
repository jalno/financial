<?php

namespace packages\financial;

use packages\base\DB\DBObject;
use packages\userpanel\Date;

class PayPortPay extends DBObject
{
    public const pending = 0;
    public const success = 1;
    public const failed = 2;
    protected $dbTable = 'financial_payports_pays';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'payport' => ['type' => 'int', 'required' => true],
        'transaction' => ['type' => 'int', 'required' => true],
        'date' => ['type' => 'int', 'required' => true],
        'price' => ['type' => 'double', 'required' => true],
        'currency' => ['type' => 'int', 'required' => true],
        'ip' => ['type' => 'text'],
        'status' => ['type' => 'int', 'required' => true],
    ];
    protected $relations = [
        'payport' => ['hasOne', 'packages\\financial\\payport', 'payport'],
        'transaction' => ['hasOne', 'packages\\financial\\transaction', 'transaction'],
        'params' => ['hasMany', 'packages\\financial\\payport_pay_param', 'pay'],
        'currency' => ['hasOne', Currency::class, 'currency'],
    ];

    public function __construct($data = null, $connection = 'default')
    {
        $data = $this->processData($data);
        parent::__construct($data, $connection);
    }
    protected $tmparams = [];

    public function verification()
    {
        return $this->payport->PaymentVerification($this);
    }

    private function processData($data)
    {
        $newdata = [];
        if (is_array($data)) {
            if (isset($data['params'])) {
                foreach ($data['params'] as $name => $value) {
                    $this->tmparams[$name] = new PayportPayParam([
                        'name' => $name,
                        'value' => $value,
                    ]);
                }
                unset($data['params']);
            }
            $newdata = $data;
        }
        if (!isset($data['date'])) {
            $newdata['date'] = Date::time();
        }

        if (!isset($data['status'])) {
            $newdata['status'] = self::pending;
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
            $param = new PayportPayParam([
                'name' => $name,
                'value' => $value,
            ]);
        } else {
            $param->value = $value;
        }

        if (!$this->id or $this->isNew) {
            $this->tmparams[$name] = $param;
        } else {
            $param->pay = $this->id;

            return $param->save();
        }
    }

    public function save($data = null)
    {
        if ($return = parent::save($data)) {
            foreach ($this->tmparams as $param) {
                $param->pay = $this->id;
                $param->save();
            }
            $this->tmparams = [];
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
}
