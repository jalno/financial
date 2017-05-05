<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\pay\delete as payDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;

class delete extends payDelete{
	use viewTrait, formTrait;
	protected $pay;
	function __beforeLoad(){
		$this->pay = $this->getPayData();
		$this->setTitle(array(
			translator::trans('transaction.pay.delete'),
			$this->pay->id
		));
		$this->setShortDescription(translator::trans('transaction.pay.delete'));
		$this->setNavigation();
	}

	private function setNavigation(){
		$item = new menuItem("transactions");
		$item->setTitle(translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction");
		$item->setTitle(translator::trans('transaction.edit'));
		$item->setURL(userpanel\url('transactions/edit/'.$this->pay->transaction->id));
		$item->setIcon('fa fa-edit');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction.pay");
		$item->setTitle(translator::trans('transaction.pay.delete'));
		$item->setIcon('fa fa-trash');
		breadcrumb::addItem($item);
		navigation::active("transactions/list");
	}
}
