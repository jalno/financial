<?php
namespace packages\financial\views\settings\currencies;
use \packages\userpanel\date;
use \packages\financial\currency;
use \packages\financial\views\form;
class edit extends form{
	use currenciesTrait;
	public function setCurrency(currency $currency){
		$this->setData($currency, "currency");
		$this->setDataForm($currency->toArray());
		$this->setDataForm(date::format("Y/m/d H:i:s", $currency->update_at), "update_at");
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
