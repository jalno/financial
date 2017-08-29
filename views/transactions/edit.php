<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\form;
use \packages\financial\transaction;
use \packages\financial\authorization;
class edit extends form{
	protected $canPayAccept;
	protected $canPayReject;
	protected $canPaydelete;
	protected $canEditProduct;
	protected $canDeleteProduct;
	function __construct(){
		$this->canPayAccept = $this->canPayReject = authorization::is_accessed('transactions_pays_accept');
		$this->canEditProduct = authorization::is_accessed('transactions_product_edit');
		$this->canDeleteProduct = authorization::is_accessed('transactions_product_delete');
		$this->canPaydelete = authorization::is_accessed('transactions_pay_delete');
	}
	public function setTransactionData(transaction $transaction){
		$this->setData($transaction, 'transaction');
		$this->setDataForm($transaction->toArray());
	}
	public function getTransactionData():transaction{
		return $this->getData('transaction');
	}
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
}