<?php

namespace packages\financial\Currency;

use packages\base\DB\DBObject;

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
        'currency' => ['hasOne', 'packages\\financial\\currency', 'currency'],
        'changeTo' => ['hasOne', 'packages\\financial\\currency', 'changeTo'],
    ];
}
