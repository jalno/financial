<?php
namespace packages\financial\views\transactions\pay\onlinepay;
use \packages\financial\view;
use \packages\financial\payport_pay;
class error extends view{
	public function setPay(payport_pay $pay){
		$this->setData($pay, 'pay');
	}
	public function getPay(){
		return $this->getData('pay');
	}
	public function setError($error, $message = null){
		$this->setData(array(
			'error' => $error,
			'message' => $message
		), 'error');
	}
	public function getError(){
		return $this->getData('error');
	}
}
