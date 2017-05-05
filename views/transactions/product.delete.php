<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\form;
use \packages\financial\transaction_product;
class product_delete extends form{
	public function setProduct(transaction_product $product){
		$this->setData($product, 'product');
	}
	public function getProduct():transaction_product{
		return $this->getData('product');
	}
}
