<?php
namespace packages\financial\views\transactions;
use \packages\financial\transaction;
use \packages\financial\authorization;
class view extends \packages\financial\view{
	protected $canPayAccept;
	protected $canPayReject;
	function __construct(){
		$this->canPayAccept = $this->canPayReject = authorization::is_accessed('transactions_pays_accept');
	}
	public function settransactionData($data){
		$this->setData($data, 'user');
	}
	public function getUserData($key){
		return($this->data['user']->$key);
	}
}
