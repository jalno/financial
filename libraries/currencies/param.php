<?php
namespace packages\financial\currency;
use \packages\base\db\dbObject;
class param extends dbObject{
	protected $dbTable = "financial_currencies_params";
	protected $primaryKey = "id";
	private $hadlerClass;
	protected $dbFields = array(
		'currency' => array('type' => 'int', 'required' => true),
		'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text', 'required' => true)
    );
}
