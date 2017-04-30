<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\accept as transactionsAccept;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;

class accept extends transactionsAccept{
	use viewTrait,listTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('transactions'),
			translator::trans("financial.transaction.accept")
		));
		navigation::active("transactions/list");
	}
}
