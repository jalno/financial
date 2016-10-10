<?php
namespace packages\financial\views\transactions;

use \packages\financial\authorization;
class edit extends \packages\financial\views\form{
	protected $canPayAccept;
	protected $canPayReject;
	function __construct(){
		$this->canPayAccept = $this->canPayReject = authorization::is_accessed('transactions_pays_accept');
	}
	public function setTransactionData($data){
		$this->setData($data, 'transaction');
	}
	public function getTransactionData(){
		return $this->getData('transaction');
	}
}
