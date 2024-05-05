<?php
namespace packages\financial\Views\Transactions;
use \packages\financial\Transaction;
use \packages\financial\Authorization;
trait PayTrait{
	public function setTransaction(Transaction $transaction){
		$this->setData($transaction, 'transaction');
	}
	public function getTransaction(){
		return($this->getData('transaction'));
	}
}
class Pay  extends \packages\financial\View{
	use PayTrait;
	protected $canAccept;
	protected $canViewGuestLink;
	function __construct(){
		$this->canAccept = Authorization::is_accessed('transactions_accept');
		$this->canViewGuestLink = Authorization::is_accessed('transactions_guest-pay-link');
		$this->setData(array(), 'methods');
	}
	public function setCredit($credit){
		$this->setData($credit, 'credit');
	}
	public function setBankAccounts($accounts){
		$this->setData($accounts, 'bankaccounts');
	}
	public function setPayPorts($ports){
		$this->setData($ports, 'payports');
	}
	public function setMethod($method){
		$this->data['methods'][] = $method;
	}
	public function getMethods(){
		return $this->getData('methods');
	}
	public function export() {
		return array(
			'data' => array(
				'methods' => $this->getMethods(),
			)
		);
	}
}
