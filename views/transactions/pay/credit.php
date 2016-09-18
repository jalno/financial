<?php
namespace packages\financial\views\transactions\pay;
use \packages\financial\views\form;
use \packages\financial\views\transactions\payTrait;
class credit  extends form{
	use payTrait;
	public function setCredit($credit){
		$this->setData($credit, 'credit');
	}
	public function getCredit(){
		return $this->getData('credit');
	}
}
