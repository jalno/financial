<?php
namespace packages\financial\Events\GateWays;

class InputNameException extends \Exception {
	private $input;
	public function __construct($input){
		$this->input = $input;
	}
	public function getController(){
		return $this->input;
	}
}
