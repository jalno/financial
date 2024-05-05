<?php
namespace packages\financial\Currency;
use \packages\base\DB\DBObject;
class Param extends DBObject{
	protected $dbTable = "financial_currencies_params";
	protected $primaryKey = "id";
	private $hadlerClass;
	protected $dbFields = array(
		'currency' => array('type' => 'int', 'required' => true),
		'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text', 'required' => true)
    );
}
