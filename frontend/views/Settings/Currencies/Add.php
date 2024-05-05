<?php
namespace themes\clipone\views\financial\Settings\Currencies;

use packages\base\{Translator};
use packages\financial\{Currency};
use packages\userpanel\{Date};
use themes\clipone\{Views\FormTrait, Navigation, ViewTrait};
use packages\financial\Views\Settings\Currencies\Add as CurrenciesAdd;

class Add extends CurrenciesAdd {
	use ViewTrait, FormTrait;

	private $currencies;

	public function __beforeLoad(): void {
		$this->currencies = $this->getCurrencies();
		$this->setTitle([
			Translator::trans("settings.financial.currencies"),
			Translator::trans("settings.financial.currency.add")
		]);
		Navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-add');
		$this->setFormData();
	}
	private function setFormData(): void {
		if (!$this->getDataForm('update_at')) {
			$this->setDataForm(Date::format('Y/m/d H:i:s'), 'update_at');
		}
		if (!$this->getDataForm('rounding-behaviour')) {
			$this->setDataForm(Currency::ROUND, 'rounding-behaviour');
		}
		if (!$this->getDataForm('rounding-precision')) {
			$this->setDataForm(0, 'rounding-precision');
		}
	}
	protected function geCurrenciesForSelect(): array {
		$currencies = [];
		foreach($this->currencies as $currency){
			$currencies[] = [
				'title' => $currency->title,
				'value' => $currency->id
			];
		}
		return $currencies;
	}
	protected function getRoundingBehavioursForSelect(): array {
		return array(
			array(
				'title' => t('financial.setting.currency.rounding_behaviour.ceil'),
				'value' => Currency::CEIL,
			),
			array(
				'title' => t('financial.setting.currency.rounding_behaviour.round'),
				'value' => Currency::ROUND,
			),
			array(
				'title' => t('financial.setting.currency.rounding_behaviour.floor'),
				'value' => Currency::FLOOR,
			),
		);
	}
}
