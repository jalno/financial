<?php
namespace packages\financial\views\settings\bankaccount;
use \packages\financial\views\form;
use \packages\financial\bankaccount;
class edit extends form{
	public function setBankaccount(bankaccount $bankaccount){
		$this->setData($bankaccount, "bankaccount");
		$this->setDataForm($bankaccount->title, "title");
		$this->setDataForm($bankaccount->account, "account");
		$this->setDataForm($bankaccount->cart, "cart");
		$this->setDataForm($bankaccount->owner, "owner");
		$this->setDataForm($bankaccount->status, "status");
	}
	public function getBankaccount(){
		return $this->getData("bankaccount");
	}
}
