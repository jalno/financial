<?php
namespace packages\financial\Views\Transactions\Pay;

class Delete extends \packages\financial\Views\Form{
	public function setPayData($data){
		$this->setData($data, 'transaction');
	}
	public function getPayData(){
		return $this->getData('transaction');
	}
}
