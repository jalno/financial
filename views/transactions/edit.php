<?php
namespace packages\financial\views\transactions;

class edit extends \packages\financial\views\form{
	public function setTransactionData($data){
		$this->setData($data, 'transaction');
	}
	public function getTransactionData(){
		return $this->getData('transaction');
	}
}
