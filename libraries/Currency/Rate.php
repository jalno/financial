<?php

namespace packages\financial\Currency;

use packages\base\DB\DBObject;
use packages\financial\Currency;

class Rate extends DBObject
{
    protected $dbTable = 'financial_currencies_rates';
    protected $primaryKey = 'id';
    private $hadlerClass;
    protected $dbFields = [
        'currency' => ['type' => 'int', 'required' => true],
        'changeTo' => ['type' => 'int', 'required' => true],
        'price' => ['type' => 'double', 'required' => true],
    ];
    protected $relations = [
        'currency' => ['hasOne', Currency::class, 'currency'],
        'changeTo' => ['hasOne', Currency::class, 'changeTo'],
    ];
}
