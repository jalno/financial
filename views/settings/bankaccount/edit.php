<?php
namespace packages\financial\views\settings\bankaccount;
use \packages\financial\views\form;
use \packages\financial\bankaccount;
class edit extends form{
	public function setBankaccount(bankaccount $bankaccount){
		$this->setData($bankaccount, "bankaccount");
		$this->setDataForm($bankaccount->toArray());
	}
	public function getBankaccount(){
		return $this->getData("bankaccount");
	}
}
