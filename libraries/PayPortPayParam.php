<?php

namespace packages\financial;

use packages\base\DB\DBObject;

class PayPortPayParam extends DBObject
{
    protected $dbTable = 'financial_payports_pays_params';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'pay' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'text', 'required' => true],
        'value' => ['type' => 'text', 'required' => true],
    ];

    protected $jsonFields = ['value'];
}
