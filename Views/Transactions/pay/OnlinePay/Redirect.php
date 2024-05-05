<?php
namespace packages\financial\Views\Transactions\Pay\OnlinePay;
use \packages\financial\Views\Form;
use \packages\financial\Transaction;
use \packages\financial\PayPort\Redirect as PayPortRedirect;
class Redirect extends Form{
	public function setTransaction(Transaction $transaction){
		$this->setData($transaction, 'transaction');
	}
	public function getTransaction(){
		return $this->getData('transaction');
	}
	public function setRedirect(PayPortRedirect $redirect){
		$this->setData($redirect, 'redirect');
	}
	public function getRedirect(){
		return $this->getData('redirect');
	}
	public function export() {
		return array(
			'data' => array(
				'redirect' => $this->getRedirect(),
			)
		);
	}
}
