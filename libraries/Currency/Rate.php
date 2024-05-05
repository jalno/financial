<?php
namespace packages\financial\Currency;
use \packages\base\DB\DBObject;
class Rate extends DBObject{
	protected $dbTable = "financial_currencies_rates";
	protected $primaryKey = "id";
	private $hadlerClass;
	protected $dbFields = array(
		'currency' => array('type' => 'int', 'required' => true),
		'changeTo' => array('type' => 'int', 'required' => true),
        'price' => array('type' => 'double', 'required' => true)
    );
	protected $relations = [
		'currency' => ['hasOne', 'packages\\financial\\currency', 'currency'],
		'changeTo' => ['hasOne', 'packages\\financial\\currency', 'changeTo']
	];
}
