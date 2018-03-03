<?php
namespace packages\financial;
use \packages\base\db\dbObject;
use \packages\userpanel\date;
class payport_pay extends dbObject{
	const pending = 0;
	const success = 1;
	const failed = 2;
	protected $dbTable = "financial_payports_pays";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'payport' => array('type' => 'int', 'required' => true),
        'transaction' => array('type' => 'int', 'required' => true),
        'date' => array('type' => 'int', 'required' => true),
		'price' => array('type' => 'double', 'required' => true),
		"currency" => array("type" => "int", "required" => true),
        'ip' => array('type' => 'text'),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'payport' => array('hasOne', 'packages\\financial\\payport', 'payport'),
		'transaction' => array('hasOne', 'packages\\financial\\transaction', 'transaction'),
		'params' => array('hasMany', 'packages\\financial\\payport_pay_param', 'pay'),
		"currency" => array("hasOne", currency::class, "currency"),
	);
	function __construct($data = null, $connection = 'default'){
		$data = $this->processData($data);
		parent::__construct($data, $connection);
	}
	protected $tmparams = array();
	public function verification(){
		return $this->payport->PaymentVerification($this);
	}
	private function processData($data){
		$newdata = array();
		if(is_array($data)){
			if(isset($data['params'])){
				foreach($data['params'] as $name => $value){
					$this->tmparams[$name] = new payport_pay_param(array(
						'name' => $name,
						'value' => $value
					));
				}
				unset($data['params']);
			}
			$newdata = $data;
		}
		if(!isset($data['date'])){
			$newdata['date'] = date::time();
		}

		if(!isset($data['status'])){
			$newdata['status'] = self::pending;
		}
		return $newdata;
	}
	public function setParam($name, $value){
		$param = false;
		foreach($this->params as $p){
			if($p->name == $name){
				$param = $p;
				break;
			}
		}
		if(!$param){
			$param = new payport_pay_param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id or $this->isNew){
			$this->tmparams[$name] = $param;
		}else{
			$param->pay = $this->id;
			return $param->save();
		}
	}
	public function save($data = null) {
		if($return = parent::save($data)){
			foreach($this->tmparams as $param){
				$param->pay = $this->id;
				$param->save();
			}
			$this->tmparams = array();
		}
		return $return;
	}
	public function param($name){
		if(!$this->id){
			return(isset($this->tmparams[$name]) ? $this->tmparams[$name]->value : null);
		}else{
			foreach($this->params as $param){
				if($param->name == $name){
					return $param->value;
				}
			}
			return false;
		}
	}
}
