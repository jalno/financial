<?php
namespace packages\financial\views\transactions\pay\onlinepay;
use \packages\financial\views\form;
use \packages\financial\transaction;
use \packages\financial\payport\redirect as payport_redirect;
class redirect extends form{
	public function setTransaction(transaction $transaction){
		$this->setData($transaction, 'transaction');
	}
	public function getTransaction(){
		return $this->getData('transaction');
	}
	public function setRedirect(payport_redirect $redirect){
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
