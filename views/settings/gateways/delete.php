<?php
namespace packages\financial\views\settings\gateways;
use \packages\financial\payport;
use \packages\userpanel\views\form;
class delete extends form{
	public function setGateway(payport $gateway){
		$this->setData($gateway, "gateway");
	}
	protected function getGateway(){
		return $this->getData('gateway');
	}
}
