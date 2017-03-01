<?php
namespace packages\financial\events;
use \packages\base\event;
use \packages\financial\events\gateways\gateway;
class gateways extends event{
	private $gateways = array();
	public function addGateway(gateway $gateway){
		$this->gateways[$gateway->getName()] = $gateway;
	}
	public function getGatewayNames(){
		return array_keys($this->gateways);
	}
	public function getByName($name){
		return (isset($this->gateways[$name]) ? $this->gateways[$name] : null);
	}
	public function getByHandler($handler){
		foreach($this->gateways as $gateway){
			if($gateway->getHandler() == $handler){
				return $gateway;
			}
		}
		return null;
	}
	public function get(){
		return $this->gateways;
	}
}
