<?php
namespace themes\clipone\views\transactions;
use \packages\financial\views\transactions\product_delete as productDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \packages\base\translator;

class product_delete extends productDelete{
	use viewTrait,formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('transaction.product.delete'),
			$this->getProductData()->id
		));
		$this->setShortDescription(translator::trans('transaction.product.delete'));
	}
}
