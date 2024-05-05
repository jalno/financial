<?php
namespace packages\financial\Views\Transactions\Pay\OnlinePay;
use \packages\financial\View;
use \packages\financial\PayPortPay;
class Error extends View{
	public function setPay(PayPortPay $pay){
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
	public function export() {
		return array(
			'data' => array(
				'error' => $this->getError(),
			)
		);
	}
}
