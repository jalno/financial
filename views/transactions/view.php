<?php
namespace packages\financial\views\transactions;
use \packages\base\view\error;
use \packages\financial\transaction;
use \packages\financial\authorization;
use \packages\base\views\traits\form as formTrait;
class view extends \packages\financial\view {
	use formTrait;
	protected $canPayAccept;
	protected $canPayReject;
	protected $canAcceptRefund;
	public function __construct(){
		$this->canPayAccept = $this->canPayReject = authorization::is_accessed('transactions_pays_accept');
		$this->canAcceptRefund = authorization::is_accessed("transactions_refund_accept");
	}
	public function settransactionData($data){
		$this->setData($data, 'user');
	}
	public function getUserData($key){
		return($this->data['user']->$key);
	}
	public function setTransaction(transaction $transaction){
		$this->setData($transaction, "transaction");
		if($transaction->status == transaction::paid and !$transaction->isConfigured()){
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
