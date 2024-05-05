<?php
namespace packages\financial\Events\GateWays;
use \packages\base\Event;
class GateWay{
	private $name;
	private $handler;
	private $inputs = array();
	private $fields = array();
	private $controller;
	function __construct($name){
		$this->setName($name);
	}
	public function setName($name){
		$this->name = $name;
	}
	public function getName(){
		return $this->name;
	}
	public function setHandler($handler){
		$this->handler = $handler;
	}
	public function getHandler(){
		return $this->handler;
	}
	public function addInput($input){
		if(isset($input['name'])){
			$this->inputs[$input['name']] = $input;
		}else{
			throw new InputNameException($input);
		}
	}
	public function getInputs(){
		return $this->inputs;
	}
	public function addField($field){
		$this->fields[] = $field;
	}
	public function getFields(){
		return $this->fields;
	}
	public function setController($controller){
		$explode = explode("@", $controller, 2);
		if(class_exists($explode[0]) and method_exists($explode[0], $explode[1])){
			$this->controller = $explode;
		}else{
			throw new ControllerException($controller);
		}
	}
	public function callController($inputs){
		if($this->controller){
			$class = new $this->controller[0];
			$method = $this->controller[1];
			$class->$method($inputs);
		}
	}

}
