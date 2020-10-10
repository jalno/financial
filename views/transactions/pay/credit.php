<?php
namespace packages\financial\views\transactions\pay;

use packages\financial\{Currency, views\Form, views\transactions\PayTrait};

class Credit  extends Form {
	use payTrait;
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
