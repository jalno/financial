<?php
namespace packages\financial\views\transactions;

class product_delete extends \packages\financial\views\form{
	public function setProductData($data){
		$this->setData($data, 'transaction');
	}
	public function getProductData(){
		return $this->getData('transaction');
	}
}
