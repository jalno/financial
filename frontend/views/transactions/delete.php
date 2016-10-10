<?php
namespace themes\clipone\views\transactions;
use \packages\financial\views\transactions\delete as transactionsDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \packages\base\translator;

class delete extends transactionsDelete{
	use viewTrait,listTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('delete'),
			$this->getTransactionData()->id
		));
		$this->setShortDescription(translator::trans('transaction.delete'));
	}
}
