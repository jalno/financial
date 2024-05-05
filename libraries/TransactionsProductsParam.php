<?php

namespace packages\financial;

use packages\base\DB\DBObject;

class TransactionsProductsParam extends DBObject
{
    protected $dbTable = 'financial_transactions_products_params';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'product' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'text', 'required' => true],
        'value' => ['type' => 'text', 'required' => true],
    ];
    protected $serializeFields = ['value'];
}
