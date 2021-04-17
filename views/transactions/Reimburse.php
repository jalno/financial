<?php
namespace packages\financial\views\transactions;

use packages\base\{views\traits\Form};
use packages\financial\{Transaction, View};

class Reimburse extends View {
	use Form;

	public function setTransaction(Transaction $transaction): void {
		$this->setData($transaction, "transaction");
	}
	public function getTransaction(): Transaction {
		return $this->getData("transaction");
	}
	public function setPays(array $pays): void {
		$this->setData($pays, "pays");
	}
	public function getPays(): array {
		return $this->getData("pays") ?? [];
	}
}
