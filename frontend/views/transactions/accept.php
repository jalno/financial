<?php
namespace themes\clipone\views\transactions;

use packages\base;
use packages\userpanel;
use packages\financial\{Transaction, Transaction_Pay};
use themes\clipone\{BreadCrumb, views\ListTrait, Navigation\MenuItem, Navigation, ViewTrait};
use packages\financial\views\transactions\Accept as TransactionsAccept;

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
		return (int) (new Transaction_Pay())
		->where("transaction", $this->transaction->id)
		->where("status", Transaction_Pay::PENDING)
		->count();
	}
}
