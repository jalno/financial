<?php
namespace themes\clipone\views\transactions;

use packages\base\{Options, Packages};
use packages\financial\{Currency, Transaction, Transaction_Pay, views\transactions\Reimburse as TransactionReimburse};
use themes\clipone\{ViewTrait, views\ListTrait, views\FormTrait, breadcrumb, Navigation, views\TransactionTrait};

class Reimburse extends TransactionReimburse {
	use viewTrait, listTrait, formTrait, TransactionTrait;
	
	/** @var Transaction|null */
	protected $transaction;

	/** @var Transaction_Pay[] */
	protected $pays = [];

	/** @var Curreny|null */
	protected $userDefaultCurrency;

	/** @var int[] */
	protected $notRefundablePays = [];

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->pays = $this->getPays();
		$this->userDefaultCurrency = Currency::getDefault($this->transaction->user);
		Navigation::active("transactions/list");

		$this->setTitle(t("packages.financial.reimburse.title"));
		$this->setShortDescription(t("transaction.number", array(
			"number" =>  $this->transaction->id)
		));

		$this->addBodyClass("transactions");
		$this->addBodyClass("transaction-reimburse");
	}

	protected function getPaysTotalAmountByCurrency(): int {
		$currency = $this->userDefaultCurrency;
		return array_reduce($this->getPays(), function($carry, Transaction_Pay $pay) use (&$currency) {
			$price = 0;
			try {
				$price = $pay->currency->changeTo($pay->price, $currency);
			} catch (Currency\UnChangableException $e) {
				$this->notRefundablePays[] = $pay->id;
			}
			return $carry + $price;
		}, 0);
	}

}
