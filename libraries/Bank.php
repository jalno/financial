<?php

namespace packages\financial;

use packages\base\DB\DBObject;

class Bank extends DBObject
{
    public const Active = 1;
    public const Deactive = 2;
    protected $dbTable = 'financial_banks';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'title' => ['type' => 'text', 'required' => true, 'unique' => true],
        'status' => ['type' => 'int', 'required' => true],
    ];

    public function preLoad(array $data): array
    {
        if (!isset($data['status'])) {
            $data['status'] = self::Active;
        }

        return $data;
    }
}
