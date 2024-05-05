<?php
namespace themes\clipone\views\financial\Settings\Currencies;
use \packages\base\Translator;
use \themes\clipone\ViewTrait;
use \themes\clipone\Navigation;
use \themes\clipone\Views\FormTrait;
use \packages\financial\Views\Settings\Currencies\Delete as CurrenciesDelete;
class Delete extends CurrenciesDelete{
	use ViewTrait, FormTrait;
	protected $currency;
	function __beforeLoad(){
		$this->currency = $this->getCurrency();
		$this->setTitle([
			Translator::trans("settings.financial.currencies"),
			Translator::trans("settings.financial.currency.delete")
		]);
		Navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-delete');
	}
}
