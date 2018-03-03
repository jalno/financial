<?php
namespace packages\financial\views\transactions\pay;
use \packages\financial\{views\form, transaction};

class onlinepay  extends form{
	public function setTransaction(transaction $transaction){
		$this->setData($transaction, "transaction");
		$this->setDataForm($transaction->payablePrice(), "price");
		$this->setDataForm($transaction->currency->id, "currency");
	}
	public function getTransaction(){
		return($this->getData("transaction"));
	}
	public function setPayports($payports){
		$this->setData($payports, "payports");
	}
	public function getPayports(){
		return $this->getData("payports");
	}
}
