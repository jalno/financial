<?php
namespace themes\clipone\views\financial\settings\currencies;
use \packages\userpanel\date;
use \packages\base\translator;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\formTrait;
use \packages\financial\views\settings\currencies\add as currenciesADD;
class add extends currenciesADD{
	use viewTrait, formTrait;
	private $currencies;
	function __beforeLoad(){
		$this->currencies = $this->getCurrencies();
		$this->setTitle([
			translator::trans("settings.financial.currencies"),
			translator::trans("settings.financial.currency.add")
		]);
		navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-add');
		$this->setFormData();
	}
	private function setFormData(){
		if(!$this->getDataForm('update_at')){
			$this->setDataForm(date::format('Y/m/d H:i:s'), 'update_at');
		}
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
