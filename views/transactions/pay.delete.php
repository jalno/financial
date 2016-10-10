<?php
namespace packages\financial\views\transactions\pay;

class delete extends \packages\financial\views\form{
	public function setPayData($data){
		$this->setData($data, 'transaction');
	}
	public function getPayData(){
		return $this->getData('transaction');
	}
}
