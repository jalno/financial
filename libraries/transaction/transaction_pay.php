<?php
namespace packages\financial;
use \packages\base\db\dbObject;
class transaction_pay extends dbObject{
	const accepted = 1;
	const rejected = 0;
	const pending = 2;
	const credit = 1;
	const banktransfer = 2;
	const onlinepay = 3;
	protected $dbTable = "financial_transactions_pays";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'transaction' => array('type' => 'int', 'required' => true),
        'method' => array('type' => 'int', 'required' => true),
        'date' => array('type' => 'int', 'required' => true),
        'price' => array('type' => 'int', 'required' => true),
        'status' => array('type' => 'int', 'required' => true)
	);
	protected $relations = array(
		'transaction' => array('hasOne', 'packages\\financial\\transaction', 'transaction'),
		'params' => array('hasMany', 'packages\\financial\\transaction_pay_param', 'pay')
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
					$this->tmparams[$name] = new transaction_pay_param(array(
						'name' => $name,
						'value' => $value
					));
				}
				unset($data['params']);
			}
			$newdata = $data;
		}
		if(!isset($data['date'])){
			$newdata['date'] = time();
		}

		if(!isset($data['status'])){
			$newdata['status'] = self::accepted;
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
			$param = new transaction_pay_param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id){
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
			if($this->transaction->status == transaction::unpaid){
				if($this->transaction->payablePrice() == 0){
					$this->transaction->status = transaction::paid;
					$this->transaction->expire_at = null;
					$this->transaction->paid_at = time();
					$this->transaction->save();
					$this->transaction->trigger_paid();
				}
			}
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
