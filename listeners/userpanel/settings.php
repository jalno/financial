<?php
namespace packages\financial\listeners\userpanel;

use packages\base\{Options, Translator};
use packages\financial\{Authorization, Currency, controllers\userpanel\settings as Controller};
use packages\userpanel\events\settings as SettingsEvent;

class settings{
	private function getCurrencyForSelect():array{
		$currencies = [];
		foreach(currency::get() as $currency){
			$currencies[] = [
				'title' => $currency->title,
				'value' => $currency->id
			];
		}
		return $currencies;
	}
	private function getCurrencyForCheck():array{
		$currencies = [];
		foreach(currency::get() as $currency){
			$currencies[] = $currency->id;
		}
		return $currencies;
	}
	public function settings_list(SettingsEvent $settings): void {
		$canChangeCurrency = Authorization::is_accessed("profile_change_currency");
		if (!$canChangeCurrency) {
			return;
		}
		$tuning = new settingsEvent\tuning("financial");
		$tuning->setController(controller::class);
		$tuning->addInput([
			'name' => 'financial_transaction_currency',
			'type' => 'number',
			'values' => $this->getCurrencyForCheck()
		]);
		$tuning->addField([
			'name' => 'financial_transaction_currency',
			'type' => 'select',
			'label' => translator::trans("financial.usersettings.transaction.currency"),
			'options' => $this->getCurrencyForSelect()
		]);
	
		$tuning->setDataForm('financial_transaction_currency', $settings->getUser()->option('financial_transaction_currency') ? $settings->getUser()->option('financial_transaction_currency') : options::get('packages.financial.defaultCurrency'));
		$settings->addTuning($tuning);
	}
}
