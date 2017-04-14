<?php
namespace themes\clipone\views\transactions\product;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\product\config as ConfigProduct;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;

class config extends ConfigProduct{
	use viewTrait, formTrait;
	protected $product;
	function __beforeLoad(){
		$this->product = $this->getProduct();
		$this->setTitle(translator::trans('transaction.product.configure'));
		$this->setNavigation();
		$this->setErrors();
	}
	private function setNavigation(){
		navigation::active("transactions/list");
	}
	private function setErrors(){
		foreach($this->product->getErrors() as $error){
			$this->addError($error);
		}
	}
}
