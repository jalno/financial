<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\base\frontend\theme;

use \packages\financial\views\transactions\edit as transactionsEdit;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

use \packages\financial\transaction;

class edit extends transactionsEdit{
	use viewTrait,formTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('edit'),
			$this->getTransactionData()->id
		));
		$this->setShortDescription(translator::trans('transaction.edit'));
		$this->addAssets();
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.edit.js'));
	}
	protected function getStatusForSelect(){
		return array(
			array(
				"title" => translator::trans("transaction.unpaid"),
				"value" => transaction::unpaid
			),
			array(
				"title" => translator::trans("transaction.paid"),
				"value" => transaction::paid
			)
		);
	}
}
