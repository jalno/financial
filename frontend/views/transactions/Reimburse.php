<?php
namespace themes\clipone\views\transactions;

use packages\base\{Options, Packages};
use packages\financial\{Currency, Transaction, Transaction_Pay, views\transactions\Reimburse as TransactionReimburse};
use themes\clipone\{ViewTrait, views\ListTrait, views\FormTrait, breadcrumb, Navigation, views\TransactionTrait};

class Reimburse extends TransactionReimburse {
	use viewTrait, listTrait, formTrait, TransactionTrait;
	
	/** @var Transaction $transaction */
	protected $transaction;

	/** @var Transaction_Pay[] */
	protected $pays;

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->pays = $this->getPays();

		$this->setTitle(t("packages.financial.reimburse.title"));
		$this->setShortDescription(t("transaction.number", array(
			"number" =>  $this->transaction->id)
		));

		$this->addBodyClass("transactions");
		$this->addBodyClass("transaction-reimburse");
	}

	private function setNavigation(): void {
		Navigation::active("transactions/list");
	}

	protected function getPaysTotalAmountByCurrency(Currency $currency): int {
		return array_reduce($this->getPays(), function($carry, Transaction_Pay $pay) use (&$currency) {
			return $carry + $pay->currency->changeTo($pay->price, $currency);
		}, 0);
	}

}
