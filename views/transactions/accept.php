<?php
namespace packages\financial\views\transactions;
use \packages\financial\transaction;
class accept extends \packages\financial\view{
	protected $transaction;
	public function setTransactionData(transaction $transaction){
		$this->transaction = $transaction;
	}
	public function getTransactionData(){
		return $this->transaction;
	}
}
