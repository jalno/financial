<?php
namespace packages\financial\views\settings\gateways;
use \packages\financial\payport as gateway;
use \packages\userpanel\views\form;
class edit extends form{
	public function setGateways($gateways){
		$this->setData($gateways, "gateways");
	}
	protected function getGateways(){
		return $this->getData('gateways');
	}
	public function setGateway(gateway $gateway){
		$this->setData($gateway, "gateway");
		$this->setDataForm($gateway->toArray());
		foreach($gateway->params as $param){
			$this->setDataForm($param->value, $param->name);
		}
		foreach($this->getGateways() as $g){
			if($g->getHandler() == $gateway->controller){
				$this->setDataForm($g->getName(), "gateway");
				break;
			}
		}
		$this->setDataForm(array_column($gateway->getCurrencies(), 'currency'), 'currency');
	}
	protected function getGateway(){
		return $this->getData('gateway');
	}
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
}
