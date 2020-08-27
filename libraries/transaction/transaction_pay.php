<?php
namespace packages\financial;
use \packages\base\{db, db\dbObject, packages};
use packages\financial\{Bank\Account, events};

class transaction_pay extends dbObject{
	/** status */
	const PENDING = self::pending;
	const ACCEPTED = self::accepted;
	const REJECTED = self::rejected;

	/** method */
	const CREDIT = self::credit;
	const BANKTRANSFER = self::banktransfer;
	const ONLINEPAY = self::onlinepay;
	const PAYACCEPTED = self::payaccepted;


	/** old style const, we dont removed these for backward compatibility */
	const pending = 2;
	const accepted = 1;
	const rejected = 0;
	const credit = 1;
	const banktransfer = 2;
	const onlinepay = 3;
	const payaccepted = 4;

	protected $dbTable = "financial_transactions_pays";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'transaction' => array('type' => 'int', 'required' => true),
        'method' => array('type' => 'int', 'required' => true),
        'date' => array('type' => 'int', 'required' => true),
		'price' => array('type' => 'double', 'required' => true),
		"currency" => array("type" => "int", "required" => true),
        'status' => array('type' => 'int', 'required' => true),
	);
	protected $relations = array(
		'transaction' => array('hasOne', 'packages\\financial\\transaction', 'transaction'),
		'params' => array('hasMany', 'packages\\financial\\transaction_pay_param', 'pay'),
		"currency" => array("hasOne", currency::class, "currency")
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
		$return = parent::save($data);
		if ($return) {
			foreach ($this->tmparams as $param) {
				$param->pay = $this->id;
				$param->save();
			}
			$this->tmparams = array();
			if (in_array($this->transaction->status, [Transaction::PENDING, Transaction::UNPAID])) {
				if ($this->transaction->payablePrice() == 0) {
					$this->transaction->status = Transaction::PAID;
					$this->transaction->expire_at = null;
					$this->transaction->paid_at = time();
					$this->transaction->afterPay();
					$this->transaction->save();
					$event = new events\transactions\Pay($this->transaction);
					$event->trigger();
					if ($this->transaction->isConfigured()) {
						$this->transaction->trigger_paid();
					}
				} else if ($this->method == self::BANKTRANSFER and $this->status == self::PENDING and $this->transaction->status != Transaction::PENDING) {
					$this->transaction->status = Transaction::PENDING;
					$this->transaction->save();
				} else if ($this->transaction->status == Transaction::PENDING) {
					$shouldChangeTransactionStatusToUnpaid = true;
					foreach ($this->transaction->pays as $pay) {
						if ($pay->status == self::PENDING) {
							$shouldChangeTransactionStatusToUnpaid = false;
							break;
						}
					}
					if ($shouldChangeTransactionStatusToUnpaid) {
						$this->transaction->status = Transaction::UNPAID;
						$this->transaction->save();
					}
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
	public function deleteParam(string $name) {
		if ($this->isNew) {
			unset($this->tmparams[$name]);
		} else {
			db::where("pay", $this->id)
				->where("name", $name)
				->delete("financial_transactions_pays_params");
		}
	}

	public function getBanktransferBankAccount() {
		if ($this->method != self::banktransfer) {
			return null;
		}
		$bankaccount_id = $this->param("bankaccount");
		if ($bankaccount_id) {
			$bankaccount = new Account();
			$bankaccount->where("id", $bankaccount_id);
			$bankaccount = $bankaccount->getOne();
			return ($bankaccount) ? $bankaccount : null;
		}
	}

	protected function convertPrice() {
		if ($this->currency->id == $this->transaction->currency->id) {
			return $this->price;
		}
		return $this->currency->changeTo($this->price, $this->transaction->currency);
	}
}
