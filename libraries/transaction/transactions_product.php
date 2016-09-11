<?php
namespace packages\financial;
use \packages\base\db\dbObject;
use \packages\financial\transactions_product_param;
class transaction_product extends dbObject{
	const host = 1;
	const domain = 2;
	protected $dbTable = "financial_transactions_products";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'title' => array('type' => 'text'),
        'transaction' => array('type' => 'int'),
		'description' => array('type' => 'text'),
		'type' => array('type' => 'text', 'required' => true),
		'price' => array('type' => 'int', 'required' => true),
		'discount' => array('type' => 'int', 'required' => true),
		'number' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
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
