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
	use viewTrait, formTrait;
	protected $product;
	function __beforeLoad(){
		$this->product = $this->getProduct();
		$this->setTitle(array(
			translator::trans('transaction.product.delete'),
			$this->product->id
		));
		$this->setShortDescription(translator::trans('transaction.product.delete'));
		$this->setNavigation();
	}
	private function setNavigation(){
		navigation::active("transactions/list");
	}
}
