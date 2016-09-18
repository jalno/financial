<?php
namespace packages\financial\views\transactions\pay;
use \packages\financial\views\form;
use \packages\financial\views\transactions\payTrait;
class banktransfer  extends form{
	use payTrait;
	public function setBankAccounts($bankaccounts){
		$this->setData($bankaccounts, 'bankaccounts');
	}
	public function getBankAccounts(){
		return $this->getData('bankaccounts');
	}
}
