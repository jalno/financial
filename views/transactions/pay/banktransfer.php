<?php
namespace packages\financial\views\transactions\pay;

use packages\financial\views\Form;
use packages\financial\views\transactions\PayTrait;

class Banktransfer  extends Form {
	use PayTrait;

	public function setBankAccounts($bankaccounts){
		$this->setData($bankaccounts, 'bankaccounts');
	}

	public function getBankAccounts(){
		return $this->getData('bankaccounts');
	}

	public function setBanktransferPays($transaction_pays) {
		$this->setData($transaction_pays, 'transaction_pays');
	}

	public function getBanktransferPays() {
		return $this->getData('transaction_pays');
	}

}
