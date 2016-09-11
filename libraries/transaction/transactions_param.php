<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class transactions_param extends dbObject{
	protected $dbTable = "financial_transactions_params";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'transaction' => array('type' => 'int', 'required' => true),
        'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text')
	);
}
