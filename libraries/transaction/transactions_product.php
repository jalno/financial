<?php
namespace packages\financial;
use \packages\base\db\dbObject;
use \packages\financial\transactions_product_param;
class transaction_product extends dbObject{
	const host = 1;
	const domain = 2;
	const buy = 1;
	const renew = 2;
	const upgrade = 3;
	const downgrade = 4;
	const refund = 5;
	const other = 6;
	protected $dbTable = "financial_transactions_products";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'title' => array('type' => 'text', 'required' => true),
        'transaction' => array('type' => 'int', 'required' => true),
		'description' => array('type' => 'text'),
		'type' => array('type' => 'text'),
		'method' => array('type' => 'int', 'required' => true),
		'price' => array('type' => 'int', 'required' => true),
		'discount' => array('type' => 'int', 'required' => true),
		'number' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'transaction' => array('hasOne', 'packages\\financial\\transaction', 'transaction'),
		'params' => array('hasMany', 'packages\\financial\\transactions_products_param', 'product')
	);
	function __construct($data = null, $connection = 'default'){
		$data = $this->processData($data);
		parent::__construct($data, $connection);
	}
	protected $tmparams = array();
	private function processData($data){
		$newdata = array();
		if(is_array($data)){
			if(isset($data['params'])){
				foreach($data['params'] as $name => $value){
					$this->tmparams[$name] = new transactions_products_param(array(
						'name' => $name,
						'value' => $value
					));
				}
				unset($data['params']);
			}
			$newdata = $data;
		}
		if(!isset($data['number'])){
			$newdata['number'] = 1;
		}

		if(!isset($data['discount'])){
			$newdata['discount'] = 0;
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
			$param = new transactions_product_param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id){
			$this->tmparams[$name] = $param;
		}else{
			$param->product = $this->id;
			return $param->save();
		}
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
	public function save($data = null) {
		if($return = parent::save($data)){
			foreach($this->tmparams as $param){
				$param->product = $this->id;
				$param->save();
			}
			$this->tmparams = array();
		}
		return $return;
	}
}
