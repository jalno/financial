<?php
namespace packages\financial\currency;
use \packages\base\db\dbObject;
class rate extends dbObject{
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
