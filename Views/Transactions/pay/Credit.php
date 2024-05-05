<?php
namespace packages\financial\Views\Transactions\Pay;

use packages\financial\{Currency, Views\Form, Views\Transactions\PayTrait};

class Credit  extends Form {
	use PayTrait;
	public function setCredit($credit){
		$this->setData($credit, 'credit');
	}
	public function getCredit(){
		return $this->getData('credit');
	}
	public function setCurrency(Currency $currency) {
		$this->setData($currency, 'currency');
	}
	public function getCurrency(): Currency {
		return $this->getData('currency');
	}
}
