<?php
namespace packages\financial\views\transactions\pay;

class delete extends \packages\financial\view{
	public function setPayData($data){
		$this->setData($data, 'transaction');
	}
	public function getPayData(){
		return $this->getData('transaction');
	}
}
