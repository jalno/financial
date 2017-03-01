<?php
namespace packages\financial\events\gateways;
class controllerException extends \Exception {
	private $controller;
	public function __construct($controller){
		$this->controller = $controller;
	}
	public function getController(){
		return $this->controller;
	}
}
class inputNameException extends \Exception {
	private $input;
	public function __construct($input){
		$this->input = $input;
	}
	public function getController(){
		return $this->input;
	}
}
