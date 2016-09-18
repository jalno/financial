<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class bankaccount extends dbObject{
	const active = 1;
	const deactive = 0;
	protected $dbTable = "financial_bankaccounts";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'title' => array('type' => 'text', 'required' => true),
        'account' => array('type' => 'text', 'required' => true),
		'cart' => array('type' => 'text'),
		'owner' => array('type' => 'text', 'required' => true),
		'status' => array('type' => 'int', 'required' => true)
    );
}
