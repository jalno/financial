<?php
namespace packages\financial\views\transactions\pay;
use \packages\financial\views\form;
use \packages\financial\transaction_pay;
class accept  extends form{
	public function setPay(transaction_pay $pay){
		$this->setData($pay, 'pay');
	}
	public function getPay(){
		return $this->getData('pay');
	}
}
