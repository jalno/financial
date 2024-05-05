<?php
namespace packages\financial;
use \packages\base\DB\DBObject;
class PayportPayParam extends DBObject{
	protected $dbTable = "financial_payports_pays_params";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'pay' => array('type' => 'int', 'required' => true),
        'name' => array('type' => 'text', 'required' => true),
        'value' => array('type' => 'text', 'required' => true)
    );

	protected $jsonFields = array('value');
}
