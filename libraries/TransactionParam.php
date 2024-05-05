<?php

namespace packages\financial;

use packages\base\DB\DBObject;

class TransactionParam extends DBObject
{
    protected $dbTable = 'financial_transactions_params';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'transaction' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'text', 'required' => true],
        'value' => ['type' => 'text'],
    ];
    protected $jsonFields = ['value'];
}
