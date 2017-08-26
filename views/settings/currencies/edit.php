<?php
namespace packages\financial\views\settings\currencies;
use \packages\financial\currency;
use \packages\financial\views\form;
class edit extends form{
	use currenciesTrait;
	public function setCurrency(currency $currency){
		$this->setData($currency, "currency");
		$this->setDataForm($currency->toArray());
		$this->setDataForm($currency->hasRate(), 'change');
		$rates = [];
		foreach($currency->rates as $rate){
			$rates[] = [
				'price' => $rate->price,
				'currency' => $rate->changeTo->id
			];
		}
		$this->setDataForm($rates, 'rates');
	}
	protected function getCurrency():currency{
		return $this->getData("currency");
	}
}
