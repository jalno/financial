<?php
namespace packages\financial\Views\Transactions;
use \packages\financial\Views\Form;
use \packages\financial\TransactionProduct;
class ProductDelete extends Form{
	public function setProduct(TransactionProduct $product){
		$this->setData($product, 'product');
	}
	public function getProduct():TransactionProduct{
		return $this->getData('product');
	}
}
