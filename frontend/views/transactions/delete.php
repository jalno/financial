<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\delete as transactionsDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;

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
		$this->setNavigation();
	}
	private function setNavigation(){
		$item = new menuItem("transactions");
		$item->setTitle(translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction");
		$item->setTitle(translator::trans('transaction.delete'));
		$item->setURL(userpanel\url('transactions/delete/'.$this->getTransactionData()->id));
		$item->setIcon('fa fa-trash');
		breadcrumb::addItem($item);
		navigation::active("transactions/list");
	}
}
