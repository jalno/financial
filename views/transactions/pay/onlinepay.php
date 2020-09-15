<?php
namespace packages\financial\views\transactions\pay;

use packages\financial\{views\Form, Transaction};

class OnlinePay extends Form {
	public function setTransaction(transaction $transaction): void {
		$this->setData($transaction, "transaction");
		$this->setDataForm($transaction->remainPriceForAddPay(), "price");
		$this->setDataForm($transaction->currency->id, "currency");
	}
	public function getTransaction(): Transaction {
		return $this->getData("transaction");
	}
	public function setPayports($payports): void {
		$this->setData($payports, "payports");
	}
	public function getPayports(): array {
		return $this->getData("payports") ?? [];
	}
	public function export(): array {
		return array(
			'data' => array(
				'payports' => array_map(function($payport) {
					return array(
						'id' => $payport->id,
						'title' => $payport->title,
					);
				}, $this->getPayports()),
				'payablePrice' => $this->getTransaction()->remainPriceForAddPay(),
				'currency' => $this->getTransaction()->currency->toArray(false),
			)
		);
	}
}
