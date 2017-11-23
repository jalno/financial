<?php
namespace packages\financial;
use \packages\base\options;
use \packages\userpanel\user;
use \packages\userpanel\date;
use \packages\financial\events;
use \packages\base\db\dbObject;
class transaction extends dbObject{
	const unpaid = 1;
	const paid = 2;
	const refund = 3;
	const expired = 4;
	const host = 1;
	const domain = 2;
	protected $dbTable = "financial_transactions";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'user' => array('type' => 'int', 'required' => true),
        'title' => array('type' => 'text', 'required' => true),
        'price' => array('type' => 'double', 'required' => true),
		'create_at' => array('type' => 'int', 'required' => true),
		'expire_at' => array('type' => 'int'),
		'paid_at' => array('type' => 'int'),
		'currency' => array('type' => 'int', 'required' => true),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'user' => array('hasOne', 'packages\\userpanel\\user', 'user'),
		'currency' => array('hasOne', 'packages\\financial\\currency', 'currency'),
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
			$pay->save();
			return $pay->id;
		}
	}
	protected function payablePrice(){
		$payable = $this->totalPrice();
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
		if(!isset($data['currency'])){
			if(!$data['user'] instanceof dbObject){
				$user = user::where('id', $data['user'])->getOne();
			}else{
				$user = $data['user'];
			}
			$data['currency'] = currency::getDefault($user);
		}
		if($data['currency'] instanceof dbObject){
			$data['currency'] = $data['currency']->id;
		}
		$products = array();
		if ($this->isNew){
			$products = &$this->tmproduct;
		}else{
			$products = $this->products;
		}
		$data['price'] = 0;
		foreach($products as $product){
			$price = $product->price;
			$discount = $product->discount;
			if(!$product->currency){
				if(!$data['user'] instanceof dbObject){
					$user = user::where('id', $data['user'])->getOne();
				}else{
					$user = $data['user'];
				}
				$product->currency = currency::getDefault($user);
			}
			if($data['currency'] != $product->currency->id){
				$rate = new currency\rate();
				$rate->where('currency', $product->currency->id);
				$rate->where('changeTo', $data['currency']);
				if(!$rate = $rate->getOne()){
					throw new currency\UnChangableException($product->currency, $data['currency']);
				}
				$price *= $rate->price;
				$discount *= $rate->price;
			}
			$data['price'] += (($price*$product->number) - $discount);
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
			foreach($this->tmparams as $param){
				$param->transaction = $this->id;
				$param->save();
			}
			$this->tmparams = array();
			foreach($this->tmpays as $pay){
				$pay->transaction = $this->id;
				$pay->save();
			}
			$this->tmpays = array();
		}
		return $return;
	}
	public function deleteParam(string $name):bool{
		if(!$this->id){
			if(isset($this->tmparams[$name])){
				unset($this->tmparams[$name]);
			}
		}else{
			$param = new transaction_param();
			$param->where('transaction', $this->id);
			$param->where('name', $name);
			if($param = $param->getOne()){
				return $param->delete();
			}
		}
		return true;
	}
	public static function checkExpiration(){
		$transaction = new transaction();
		$transaction->where('status', self::unpaid);
		$transaction->where('expire_at', date::time(), '<');
		foreach($transaction->get() as $transaction){
			$transaction->status = self::expired;
			$transaction->save();
			$event = new events\transactions\expire($transaction);
			$event->trigger();
		}
	}
	public function afterPay(){
		foreach($this->products as $product){
			$currency = $this->currency;
			$pcurrency = $product->currency;
			if($pcurrency->id != $currency->id){
				$rate = new currency\rate();
				$rate->where('currency', $pcurrency->id);
				$rate->where('changeTo', $currency->id);
				$rate = $rate->getOne();
				$product->price *= $rate->price;
				$product->discount *= $rate->discount;
				$product->currency = $currency->id;
				$product->save();
			}
		}
	}
	public function totalPrice():float{
		$currency = $this->currency;
		$price = 0;
		$needChange = false;
		foreach($this->products as $product){
			$pcurrency = $product->currency;
			if($pcurrency->id != $currency->id){
				$price += $pcurrency->changeTo(($product->price * $product->number) - $product->discount, $currency);
				$needChange = true;
			}else{
				$price += ($product->price * $product->number) - $product->discount;
			}
		}
		return $needChange ? $price : $this->price;
	}
}
class undefinedCurrencyException extends \Exception{}