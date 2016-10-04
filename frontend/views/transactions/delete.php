<?php
namespace themes\clipone\views\transactions;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\bankaccount;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\financial\payport_pay;
use \packages\financial\views\transactions\delete as transactionsDelete;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
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
