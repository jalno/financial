<?php

namespace packages\financial\Currency;

use packages\base\DB\DBObject;

class Param extends DBObject
{
    protected $dbTable = 'financial_currencies_params';
    protected $primaryKey = 'id';
    private $hadlerClass;
    protected $dbFields = [
        'currency' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'text', 'required' => true],
        'value' => ['type' => 'text', 'required' => true],
    ];
}
