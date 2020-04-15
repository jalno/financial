<?php
namespace packages\financial\views\transactions;

use packages\base\{View\Error,views\traits\Form, Packages};
use packages\financial\{Transaction, Authorization};
use packages\tickting\Department;

class view extends \packages\financial\view {
	use Form;

	protected $canPayAccept;
	protected $canPayReject;
	protected $canAcceptRefund;
	public function __construct() {
		$this->canPayAccept = $this->canPayReject = Authorization::is_accessed('transactions_pay_accept');
		$this->canAcceptRefund = Authorization::is_accessed("transactions_refund_accept");
	}
	public function settransactionData($data){
		$this->setData($data, 'user');
	}
	public function getUserData($key){
		return($this->data['user']->$key);
	}
	public function setTransaction(Transaction $transaction){
		$this->setData($transaction, "transaction");
		if($transaction->status == Transaction::paid and !$transaction->isConfigured()){
			$error = new error();
			$error->setType(error::WARNING);
			$error->setCode("financial.productNeedToConfigured");
			$this->addError($error);
		}
	}
	protected function getTransaction(){
		return $this->getData('transaction');
	}
}
