<?php
namespace themes\clipone\Views\Transactions\Product;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\financial\Views\Transactions\Product\Config as ConfigProduct;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;

class Config extends ConfigProduct{
	use ViewTrait, FormTrait;
	protected $product;
	function __beforeLoad(){
		$this->product = $this->getProduct();
		$this->setTitle(Translator::trans('transaction.product.configure'));
		$this->setNavigation();
		$this->setErrors();
	}
	private function setNavigation(){
		Navigation::active("transactions/list");
	}
	private function setErrors(){
		foreach($this->product->getErrors() as $error){
			$this->addError($error);
		}
	}
}
