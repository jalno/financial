<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\product_delete as productDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;

class product_delete extends productDelete{
	use viewTrait,formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('transaction.product.delete'),
			$this->getProductData()->id
		));
		$this->setShortDescription(translator::trans('transaction.product.delete'));
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
		$item->setURL(userpanel\url('transactions/edit/'.$this->getProductData()->transaction->id));
		$item->setIcon('fa fa-edit');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction.product.delete");
		$item->setTitle(translator::trans('transaction.product.delete'));
		$item->setIcon('fa fa-trash');
		breadcrumb::addItem($item);
		navigation::active("transactions/list");
	}
}
