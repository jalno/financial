<?php
namespace themes\clipone\views\financial\Settings\Currencies;

use packages\base\{Translator};
use packages\financial\{Currency};
use themes\clipone\{Views\FormTrait, Navigation, ViewTrait};
use packages\financial\Views\Settings\Currencies\Edit as CurrenciesEdit;

class Edit extends CurrenciesEdit {
	use ViewTrait, FormTrait;

	protected $currency;
	protected $hasRate;
	private $currencies;

	public function __beforeLoad(): void {
		$this->currency = $this->getCurrency();
		$this->currencies = $this->getCurrencies();
		$this->hasRate = $this->currency->hasRate();
		$this->setTitle([
			t("settings.financial.currencies"),
			t("settings.financial.currency.edit")
		]);
		Navigation::active("settings/financial/currencies");
		$this->addBodyClass('financial-settings');
		$this->addBodyClass('currencies-edit');
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
