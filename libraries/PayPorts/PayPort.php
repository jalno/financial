<?php
namespace packages\financial;

use packages\base\DB;
use packages\base\DB\DBObject;
use packages\financial\{PayPort\Param, Bank};
use packages\financial\PayPort\{GateWayException, VerificationException};

class PayPort extends DBObject{
	const active = 1;
	const deactive = 2;
	protected $dbTable = "financial_payports";
	protected $primaryKey = "id";
	private $controllerClass;
	protected $dbFields = array(
        'title' => array('type' => 'text', 'required' => true),
        "account" => array("type" => "int"),
        'controller' => array('type' => 'text', 'required' => true),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'params' => array('hasMany', PayPort\Param::class, 'payport'),
		"account" => array("hasOne", Bank\Account::class, "account"),
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
					$this->tmparams[$name] = new param(array(
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
	public function getController(){
		if($this->controllerClass){
			return $this->controllerClass;
		}
		if(class_exists($this->controller)){
			$this->controllerClass = new $this->controller($this);
			return $this->controllerClass;
		}
		return false;
	}
	public function PaymentRequest($price, Transaction $transaction, Currency $currency, $ip = null){
		$pay = new PayPortPay();
		$pay->price = $price;
		$pay->payport = $this->id;
		$pay->transaction = $transaction->id;
		$pay->currency = $currency->id;
		if($ip){
			$pay->ip = $ip;
		}
		$pay->save();
		$controller = $this->getController();
		$redirect = $controller->PaymentRequest($pay);
		return $redirect;
	}
	public function PaymentVerification(PayPortPay $pay){
		try{
			$controller = $this->getController();
			$newstatus = $controller->PaymentVerification($pay);
			$pay->status = $newstatus;
			$pay->save();
			return $pay->status;
		}catch(GatewayException $e){
			$pay->status = PayPortPay::failed;
			$pay->save();
			throw $e;
		}catch(VerificationException $e){
			$pay->status = PayPortPay::failed;
			$pay->save();
			throw $e;
		}
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
			$param = new Param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id){
			$this->tmparams[$name] = $param;
		}else{
			$param->payport = $this->id;
			return $param->save();
		}
	}
	public function save($data = null) {
		if($return = parent::save($data)){
			foreach($this->tmparams as $param){
				$param->payport = $this->id;
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
	public function setCurrency(int $currency){
		DB::insert('financial_payports_currencies', [
			'currency' => $currency,
			'payport' => $this->id
		]);
	}
	public function getCurrency(int $currency){
		DB::where('payport', $this->id);
		DB::where('currency', $currency);
		return DB::getOne('financial_payports_currencies');
	}
	public function getCurrencies(){
		DB::where('payport', $this->id);
		return DB::get('financial_payports_currencies', null, 'financial_payports_currencies.*');
	}
	public function deleteCurrency(int $currency){
		DB::where('payport', $this->id);
		DB::where('currency', $currency);
		return DB::delete('financial_payports_currencies');
	}
	public function getCompatilbeCurrency(Currency $currency): ?Currency {
		$currencies = array_column($this->getCurrencies(), 'currency');
		if (empty($currencies)) {
			return null;
		}
		if (!in_array($currency->id, $currencies)) {
			$model = new Currency();
			$model->join(Currency\Rate::class, null, "INNER", "currency");
			$model->where("financial_currencies_rates.currency", $currencies, "IN");
			$model->where("financial_currencies_rates.changeTo", $currency->id);
			$currency = $model->getOne("financial_currencies.*");
		}
		return $currency;
	}
}
