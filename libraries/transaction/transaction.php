<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class transaction extends dbObject{
	const unpaid = 1;
	const paid = 2;
	const refund = 3;
	const host = 1;
	const domain = 2;
	protected $dbTable = "financial_transactions";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'user' => array('type' => 'int', 'required' => true),
        'title' => array('type' => 'text', 'required' => true),
        'price' => array('type' => 'int', 'required' => true),
		'create_at' => array('type' => 'int', 'required' => true),
		'expire_at' => array('type' => 'int'),
		'paid_at' => array('type' => 'int'),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'user' => array('hasOne', 'packages\\userpanel\\user', 'user'),
		'params' => array('hasMany', 'packages\\financial\\transaction_param', 'transaction'),
		'products' => array('hasMany', 'packages\\financial\\transaction_product', 'transaction'),
		'pays' => array('hasMany', 'packages\\financial\\transaction_pay', 'transaction')
	);
	protected $tmproduct = array();
	protected $tmpays = array();
	protected function addProduct($productdata){
		$product = new transaction_product($productdata);
		if ($this->isNew){
			$this->tmproduct[] = $product;
			return true;
		}else{
			$product->transaction = $this->id;
			return $product->save();
		}
	}
	protected function addPay($paydata){
		$pay = new transaction_pay($paydata);
		if ($this->isNew){
			$this->tmpays[] = $pay;
			return true;
		}else{
			$pay->transaction = $this->id;
			return $pay->save();
		}
	}
	protected function payablePrice(){
		$payable = $this->price;
		if($this->id){
			unset($this->data['pays']);
			foreach($this->pays as $pay){
				if($pay->status == transaction_pay::accepted){
					$payable -= $pay->price;
				}
			}
		}
		return $payable;
	}
	protected function trigger_paid(){
		if(!$this->param("trigered_paid")){
			$this->setParam("trigered_paid", true);
			foreach($this->products as $product){
				if($product->type and class_exists($product->type)){
					$obj = new $product->type($product->data);
					if(method_exists($obj, 'trigger_paid')){
						$obj->trigger_paid();
					}
					unset($obj);
				}
			}
		}
	}
	public function isConfigured(){
		foreach($this->products as $product){
			if(!$product->configure){
				return false;
			}
		}
		return true;
	}
	protected function preLoad($data){
		if(!isset($data['status'])){
			$data['status'] = self::unpaid;
		}
		if(!isset($data['create_at']) or !$data['create_at']){
			$data['create_at'] = time();
		}
		if($data['status'] == self::unpaid and (!isset($data['expire_at']) or !$data['expire_at'])){
			$data['expire_at'] = $data['create_at'] + (86400*2);
		}
		$products = array();
		if ($this->isNew){
			$products = &$this->tmproduct;
		}else{
			$products = $this->products;
		}
		$data['price'] = 0;
		foreach($products as $product){
			$data['price'] += (($product->price*$product->number) - $product->discount);
		}
		return $data;
	}
	protected $tmparams = array();
	public function setParam($name, $value){
		$param = false;
		foreach($this->params as $p){
			if($p->name == $name){
				$param = $p;
				break;
			}
		}
		if(!$param){
			$param = new transaction_param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id){
			$this->tmparams[$name] = $param;
		}else{
			$param->transaction = $this->id;
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
	public function save($data = null){
		if(($return = parent::save($data))){
			foreach($this->tmproduct as $product){
				$product->transaction = $this->id;
				$product->save();
			}
			$this->tmproduct = array();
			foreach($this->tmpays as $pay){
				$pay->transaction = $this->id;
				$pay->save();
			}
			$this->tmpays = array();
			foreach($this->tmparams as $param){
				$param->transaction = $this->id;
				$param->save();
			}
			$this->tmparams = array();
		}
		return $return;
	}
}
