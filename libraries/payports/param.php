<?php
namespace packages\financial\payport;
use \packages\base\db\dbObject;
class param extends dbObject{
	protected $dbTable = "financial_payports_params";
	protected $primaryKey = "id";
	private $hadlerClass;
	protected $dbFields = array(
		'payport' => array('type' => 'int', 'required' => true),
		'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text', 'required' => true)
    );
}
