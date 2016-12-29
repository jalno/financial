<?php
namespace packages\financial\views\settings\bankaccount;
use \packages\financial\views\form;
use \packages\financial\bankaccount;
class delete extends form{
	protected $bankaccount;
	public function setBankaccount(bankaccount $bankaccount){
		$this->setData($bankaccount, "bankaccount");
	}
	public function getBankaccount(){
		return $this->getData("bankaccount");
	}
}
