<?php
namespace packages\financial\views\transactions;
use \packages\financial\transaction;
trait payTrait{
	public function setTransaction(transaction $transaction){
		$this->setData($transaction, 'transaction');
	}
	public function getTransaction(){
		return($this->getData('transaction'));
	}
}
class pay  extends \packages\financial\view{
	use payTrait;
	public function setCredit($credit){
		$this->setData($credit, 'credit');
	}
	public function setBankAccounts($accounts){
		$this->setData($accounts, 'bankaccounts');
	}
	public function setPayPorts($ports){
		$this->setData($ports, 'payports');
	}
	public function setMethod($method){
		$this->data['methods'][] = $method;
	}
	public function getMethods(){
		return $this->getData('methods');
	}
}
