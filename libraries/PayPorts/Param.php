<?php

namespace packages\financial\PayPort;

use packages\base\DB\DBObject;

class Param extends DBObject
{
    protected $dbTable = 'financial_payports_params';
    protected $primaryKey = 'id';
    private $hadlerClass;
    protected $dbFields = [
        'payport' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'text', 'required' => true],
        'value' => ['type' => 'text', 'required' => true],
    ];
}
