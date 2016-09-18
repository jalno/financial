<?php
namespace packages\financial\views\transactions\pay;
use \packages\financial\views\form;
use \packages\financial\views\transactions\payTrait;
class onlinepay  extends form{
	use payTrait;
	public function setPayports($payports){
		$this->setData($payports, 'payports');
	}
	public function getPayports(){
		return $this->getData('payports');
	}
}
