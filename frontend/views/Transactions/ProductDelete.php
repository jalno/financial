<?php
namespace themes\clipone\Views\Transactions;
use \packages\base\Translator;
use \packages\userpanel;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;

class ProductDelete extends \packages\financial\Views\Transactions\ProductDelete{
	use ViewTrait, FormTrait;
	protected $product;
	function __beforeLoad(){
		$this->product = $this->getProduct();
		$this->setTitle(array(
			Translator::trans('transaction.product.delete'),
			$this->product->id
		));
		$this->setShortDescription(Translator::trans('transaction.product.delete'));
		$this->setNavigation();
	}
	private function setNavigation(){
		Navigation::active("transactions/list");
	}
}
