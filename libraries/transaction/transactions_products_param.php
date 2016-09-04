<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class transactions_products_param extends dbObject{
	protected $dbTable = "financial_transactions_products_params";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'product' => array('type' => 'int', 'required' => true),
        'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text', 'required' => true)
    );
}
