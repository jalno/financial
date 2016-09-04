<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class transactions_param extends dbObject{
	protected $dbTable = "financial_transactions_params";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'transaction' => array('type' => 'int'),
        'name' => array('type' => 'text'),
        'value' => array('type' => 'text')
	);
}
