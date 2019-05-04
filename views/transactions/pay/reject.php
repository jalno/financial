<?php
namespace packages\financial\views\transactions\pay;

use packages\financial\{transaction_pay, views\Form};

class reject  extends Form {
	public function setPay(transaction_pay $pay){
		$this->setData($pay, 'pay');
	}
	public function getPay(){
		return $this->getData('pay');
	}
}
