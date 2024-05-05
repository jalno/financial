<?php
namespace packages\financial\Views\Settings\Currencies;
use \packages\financial\Currency;
use \packages\financial\Views\Form;
class Delete extends Form{
	public function setCurrency(Currency $currency){
		$this->setData($currency, "currency");
	}
	protected function getCurrency():Currency{
		return $this->getData("currency");
	}
}
