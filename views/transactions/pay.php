<?php
namespace packages\financial\views\transactions;
use \packages\financial\transaction;
use \packages\financial\authorization;
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
	protected $canAccept;
	function __construct(){
		$this->canAccept = authorization::is_accessed('transactions_accept');
	}
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
