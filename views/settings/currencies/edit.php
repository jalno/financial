<?php
namespace packages\financial\views\settings\currencies;
use \packages\userpanel\date;
use \packages\financial\currency;
use \packages\financial\views\form;
class edit extends form{
	use currenciesTrait;
	public function setCurrency(Currency $currency){
		$this->setData($currency, "currency");
		$this->setDataForm($currency->toArray());
		$this->setDataForm($currency->rounding_behaviour, 'rounding-behaviour');
		$this->setDataForm($currency->rounding_precision, 'rounding-precision');
		$this->setDataForm(date::format("Y/m/d H:i:s", $currency->update_at), "update_at");
		$hasRate = $currency->hasRate();
		$this->setDataForm($hasRate, 'change');
		$this->setDataForm($hasRate, 'change-checkbox');
		$defaultCurrency = Currency::getDefault();
		if ($defaultCurrency) {
			$this->setDataForm($defaultCurrency->id == $currency->id, "default");
		}
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
