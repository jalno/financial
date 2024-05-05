<?php
namespace packages\financial\Views\Transactions;

class Delete extends \packages\financial\Views\Form{
	public function setTransactionData($data){
		$this->setData($data, 'transaction');
	}
	public function getTransactionData(){
		return $this->getData('transaction');
	}
}
