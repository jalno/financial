<?php
namespace themes\clipone\Views\Transactions;

use packages\base;
use packages\userpanel;
use packages\financial\{Transaction, TransactionPay};
use themes\clipone\{Breadcrumb, Views\ListTrait, Navigation\MenuItem, Navigation, ViewTrait};
use packages\financial\Views\Transactions\Accept as TransactionsAccept;

class Accept extends TransactionsAccept {
	use ListTrait, ViewTrait;

	/** @var packages\financial\Transaction */
	protected $transaction;

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransactionData();
		$this->setTitle(array(
			t('transactions'),
			t("financial.transaction.accept")
		));
		$this->addBodyClass("transaction-accept");
		Navigation::active("transactions/list");
	}
	protected function getPendingPaysCount(): int {
		return (int) (new TransactionPay())
		->where("transaction", $this->transaction->id)
		->where("status", TransactionPay::PENDING)
		->count();
	}
}
