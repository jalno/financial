<?php
namespace packages\financial\views\transactions\product;
use \packages\financial\views\form;
use \packages\financial\transaction_product;
class config extends form{
	public function setProduct(transaction_product $product){
		$this->setData($product, "product");
	}
	protected function getProduct(){
		return $this->getData("product");
	}
}
