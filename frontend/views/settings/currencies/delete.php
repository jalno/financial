<?php
namespace themes\clipone\views\financial\settings\currencies;
use \packages\base\translator;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\formTrait;
use \packages\financial\views\settings\currencies\delete as currenciesDelete;
class delete extends currenciesDelete{
	use viewTrait, formTrait;
	protected $currency;
	function __beforeLoad(){
		$this->currency = $this->getCurrency();
		$this->setTitle([
			translator::trans("settings.financial.currencies"),
			translator::trans("settings.financial.currency.delete")
		]);
		navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-delete');
	}
}
