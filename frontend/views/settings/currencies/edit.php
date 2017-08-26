<?php
namespace themes\clipone\views\financial\settings\currencies;
use \packages\base\translator;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\formTrait;
use \packages\financial\views\settings\currencies\edit as currenciesEdit;
class edit extends currenciesEdit{
	use viewTrait, formTrait;
	protected $currency;
	private $currencies;
	function __beforeLoad(){
		$this->currency = $this->getCurrency();
		$this->currencies = $this->getCurrencies();
		$this->setTitle([
			translator::trans("settings.financial.currencies"),
			translator::trans("settings.financial.currency.edit")
		]);
		navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-edit');
	}
	protected function geCurrenciesForSelect():array{
		$currencies = [];
		foreach($this->currencies as $currency){
			$currencies[] = [
				'title' => $currency->title,
				'value' => $currency->id
			];
		}
		return $currencies;
	}
}
