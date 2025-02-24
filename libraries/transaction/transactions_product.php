<?php
namespace packages\financial;

use packages\base\db\dbObject;
use packages\financial\{events\gateways\InputNameException, transactions_product_param};

class transaction_product extends dbObject{
	const host = 1;
	const domain = 2;
	const buy = 1;
	const renew = 2;
	const upgrade = 3;
	const downgrade = 4;
	const refund = 5;
	const other = 6;
	const addingcredit = 7;
	private $inputs = array();
	private $fields = array();
	private $errorsData = array();
	protected $dbTable = "financial_transactions_products";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'title' => array('type' => 'text', 'required' => true),
		'transaction' => array('type' => 'int', 'required' => true),
		'description' => array('type' => 'text'),
		'type' => array('type' => 'text'),
		'service_id' => array('type' => 'int'),
		'method' => array('type' => 'int'),
		'price' => array('type' => 'double', 'required' => true),
		'discount' => array('type' => 'double', 'required' => true),
		'number' => array('type' => 'int', 'required' => true),
		'vat' => array('type' => 'double'),
		'currency' => ['type' => 'int', 'required' => true],
		'configure' => array('type' => 'bool', 'required' => true)
	);
	protected $relations = array(
		'transaction' => array('hasOne', 'packages\\financial\\transaction', 'transaction'),
		'currency' => array('hasOne', 'packages\\financial\\currency', 'currency'),
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

		if(!isset($data['configure'])){
			$newdata['configure'] = 1;
		}

		if (!isset($data['vat'])) {
			$newdata['vat'] = 0;
		}

		if(isset($data['type']) and $data['type'] and substr($data['type'], 0, 1) != "\\"){
			$newdata['type'] = "\\".$data['type'];
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
			$param = new transactions_products_param(array(
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

	public function preLoad(array $data): array {

		if (!$data["vat"] or $data["vat"] < 0) {
			$data["vat"] = 0;
		}

		return $data;
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

	public function getPrice(Currency $currency): float
	{
		return $this->currency->changeTo($this->price, $currency);
	}

	public function getDiscount(Currency $currency): float
	{
		return $this->discount ? $this->currency->changeTo($this->discount, $currency) : 0;
	}

	public function getVat(Currency $currency, ?float $price = null): float
	{
		if (!$price) {
			$price = $this->getPrice($currency);
		}

		return $currency->round($price * abs($this->vat) / 100);
	}

	public function totalPrice(Currency $currency): float
	{
		$price = ($this->getPrice($currency) * $this->number) - $this->getDiscount($currency);

		return $price + $this->getVat($currency, $price);
	}

	public function save($data = null) {
		if($return = parent::save($data)){
			foreach($this->tmparams as $param){
				$param->product = $this->id;
				$param->save();
			}
			$this->tmparams = array();
			if($this->transaction->status == transaction::paid and $this->transaction->isConfigured()){
				$this->transaction->trigger_paid();
			}
		}
		return $return;
	}

	public function addInput($input){
		if(isset($input['name'])){
			$this->inputs[$input['name']] = $input;
		}else{
			throw new InputNameException($input);
		}
	}
	public function getInputs(){
		return $this->inputs;
	}
	public function addField($field){
		$this->fields[] = $field;
	}
	public function getFields(){
		return $this->fields;
	}
	public function addError($field){
		$this->errorsData[] = $field;
	}
	public function getErrors(){
		return $this->errorsData;
	}
	public function config($data = array()){
		if(class_exists($this->type)){
			$obj = new $this->type($this->data);
			if(method_exists($obj, 'config')){
				$obj->config($data);
				$this->fields = $obj->getFields();
				$this->inputs = $obj->getInputs();
				$this->errorsData = $obj->getErrors();
			}
		}
		return null;
	}
}
