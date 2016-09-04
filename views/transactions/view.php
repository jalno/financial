<?php
namespace packages\financial\views\transactions;

class view extends \packages\financial\view{
	public function settransactionData($data){
		$this->setData($data, 'user');
	}
	public function getUserData($key){
		return($this->data['user']->$key);
	}
}
