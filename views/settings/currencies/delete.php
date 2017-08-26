<?php
namespace packages\financial\views\settings\currencies;
use \packages\financial\currency;
use \packages\financial\views\form;
class delete extends form{
	public function setCurrency(currency $currency){
		$this->setData($currency, "currency");
	}
	protected function getCurrency():currency{
		return $this->getData("currency");
	}
}
