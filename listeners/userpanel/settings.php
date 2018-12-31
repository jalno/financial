<?php
namespace packages\financial\listeners\userpanel;
use \packages\base\options;
use \packages\base\translator;
use \packages\financial\currency;
use \packages\userpanel\events\settings as settingsEvent;
use \packages\financial\controllers\userpanel\settings as controller;
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
	public function settings_list(settingsEvent $settings){
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
