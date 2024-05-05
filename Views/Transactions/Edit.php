<?php
namespace packages\financial\Views\Transactions;
use \packages\financial\Views\Form;
use \packages\financial\Transaction;
use \packages\financial\Authorization;
use \packages\userpanel\Date;
class Edit extends Form{
	protected $canPayAccept;
	protected $canPayReject;
	protected $canPaydelete;
	protected $canEditProduct;
	protected $canDeleteProduct;
	protected $canEditPays;

	function __construct(){
		$this->canPayAccept = $this->canPayReject = Authorization::is_accessed('transactions_pay_accept');
		$this->canEditProduct = Authorization::is_accessed('transactions_product_edit');
		$this->canDeleteProduct = Authorization::is_accessed('transactions_product_delete');
		$this->canPaydelete = Authorization::is_accessed('transactions_pay_delete');
		$this->canEditPays = Authorization::is_accessed('transactions_pay_edit');
	}
	public function setTransactionData(Transaction $transaction){
		$this->setData($transaction, 'transaction');
		$this->setDataForm($transaction->toArray());
		if ($transaction->expire_at !== null) {
			$this->setDataForm(Date::format("Y/m/d H:i:s", $transaction->expire_at), "expire_at");
		}
		if ($transaction->create_at !== null) {
			$this->setDataForm(Date::format("Y/m/d H:i:s", $transaction->create_at), "create_at");
		}
	}
	public function getTransactionData():Transaction{
		return $this->getData('transaction');
	}
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
}