<?php
namespace packages\financial;

use packages\base\Date;
use \packages\base\{db, db\dbObject};
use packages\financial\{Bank\Account, events};
use packages\userpanel\CursorPaginateTrait;

class transaction_pay extends dbObject
{
	use CursorPaginateTrait;

	/** status */
	const PENDING = self::pending;
	const ACCEPTED = self::accepted;
	const REJECTED = self::rejected;
	const REIMBURSE = 3;

	/** method */
	const CREDIT = self::credit;
	const BANKTRANSFER = self::banktransfer;
	const ONLINEPAY = self::onlinepay;
	const PAYACCEPTED = self::payaccepted;


	/* old style const, we dont removed these for backward compatibility */
	const pending = 2;
	const accepted = 1;
	const rejected = 0;

	const credit = 'credit';
	const banktransfer = 'banktransfer';
	const onlinepay = 'onlinepay';
	const payaccepted = 'payaccepted';

	protected $dbTable = "financial_transactions_pays";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'transaction' => array('type' => 'int', 'required' => true),
        'method' => array('type' => 'text', 'required' => true),
        'date' => array('type' => 'int', 'required' => true),
		'price' => array('type' => 'double', 'required' => true),
		"currency" => array("type" => "int", "required" => true),
		'updated_at' => ['type' => 'int'],
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

	protected function preLoad(array $data): array
	{
		if (!isset($data['updated_at'])) {
			$data['updated_at'] = Date::time();
		}

		return $data;
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

			$this->tmparams = [];
			$hasPendingPay = (new self)->where('transaction', $this->transaction->id)
							->where('status', self::PENDING)
							->has();

			if (!$hasPendingPay and in_array($this->transaction->status, [Transaction::PENDING, Transaction::UNPAID])) {
				$payablePrice = $this->transaction->payablePrice();
				
				if ($payablePrice <= 0) {
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

					if ($payablePrice < 0) {
						try {
							$userCurrency = Currency::getDefault($this->transaction->user);
							$price = $this->currency->changeTo(abs($payablePrice), $userCurrency);
	
							DB::where('id', $this->transaction->user->id)
								->update('userpanel_users', [
									'credit' => DB::inc($price),
								]);
							$this->transaction->setParam('overpay_added_credit', [
								'price' => $price,
								'currency' => [
									'id' => $userCurrency->id,
									'title' => $userCurrency->title,
								],
							]);
						} catch (Currency\UnChangableException) {
							$this->transaction->setParam('overpay_add_credit_error', [
								'price' => $payablePrice,
								'currency' => [
									'id' => $this->transaction->currency->id,
									'title' => $this->transaction->currency->title,
								],
							]);
						}
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
	public function getBanktransferBankAccount(): ?Account {
		if ($this->method != self::banktransfer) {
			return null;
		}
		$bankaccount_id = $this->param("bankaccount");
		return ($bankaccount_id ? (new Account())->byID($bankaccount_id) : null);
	}
	public function delete() {
		$transaction = $this->transaction;
		$return = parent::delete();
		if ($return) {
			$hasPendingPay = (new self)->where("transaction", $transaction->id)
							->where("status", self::PENDING)
							->has();
			if ($hasPendingPay) {
				$transaction->status = Transaction::PENDING;
			} elseif ($transaction->payablePrice() > 0) {
				$transaction->status = Transaction::UNPAID;
			}
			$transaction->save();
		}
		return $return;
	}
	protected function convertPrice() {
		if ($this->currency->id == $this->transaction->currency->id) {
			return $this->price;
		}
		return $this->currency->changeTo($this->price, $this->transaction->currency);
	}
}
