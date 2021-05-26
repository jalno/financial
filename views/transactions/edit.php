<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\form;
use \packages\financial\transaction;
use \packages\financial\authorization;
use \packages\userpanel\date;
class edit extends form{
	protected $canPayAccept;
	protected $canPayReject;
	protected $canPaydelete;
	protected $canEditProduct;
	protected $canDeleteProduct;
	protected $canEditPays;

	function __construct(){
		$this->canPayAccept = $this->canPayReject = authorization::is_accessed('transactions_pay_accept');
		$this->canEditProduct = authorization::is_accessed('transactions_product_edit');
		$this->canDeleteProduct = authorization::is_accessed('transactions_product_delete');
		$this->canPaydelete = authorization::is_accessed('transactions_pay_delete');
		$this->canEditPays = authorization::is_accessed('transactions_pay_edit');
	}
	public function setTransactionData(transaction $transaction){
		$this->setData($transaction, 'transaction');
		$this->setDataForm($transaction->toArray());
		if ($transaction->expire_at !== null) {
			$this->setDataForm(date::format("Y/m/d H:i:s", $transaction->expire_at), "expire_at");
		}
		if ($transaction->create_at !== null) {
			$this->setDataForm(date::format("Y/m/d H:i:s", $transaction->create_at), "create_at");
		}
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