<?php
namespace packages\financial\views\transactions;

class delete extends \packages\financial\views\form{
	public function setTransactionData($data){
		$this->setData($data, 'transaction');
	}
	public function getTransactionData(){
		return $this->getData('transaction');
	}
}
