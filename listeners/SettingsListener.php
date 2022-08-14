<?php
namespace packages\financial\listeners;

use packages\base\Options;
use packages\userpanel\events\General\Settings;
use packages\financial\controllers\Settings as Controller;
use packages\financial\Currency;
use packages\financial\validators\CheckoutLimitValidator;

class SettingsListener
{
	public function init(Settings $settings): void {
		$setting = new Settings\Setting("financial");
		$setting->setController(Controller::class);
		$this->addCheckoutLimitsFields($setting);
		$settings->addSetting($setting);
	}

	private function addCheckoutLimitsFields(Settings\Setting $setting): void {
		$defaultCurrency = Currency::getDefault();

        $setting->addInput([
            'name' => 'financial_checkout_limits',
            'type' => CheckoutLimitValidator::class,
            'optional' => true,
        ]);

        $setting->addField([
            'name' => "financial_checkout_limits[price]",
            'ltr' => true,
            'label' => t('titles.checkout_limits.price'),
            'input-group' => [
                'first' => [
                    [
                        'type' => 'addon',
                        'text' => $defaultCurrency ? $defaultCurrency->title : '',
                    ],
                ],
            ],
        ]);

        $setting->addField([
            'name' => "financial_checkout_limits[currency]",
            'type' => 'hidden',
        ]);

        $setting->addField([
            'name' => "financial_checkout_limits[period]",
            'ltr' => true,
            'label' => t('titles.checkout_limits.period'),
            'input-group' => [
                'first' => [
                    [
                        'type' => 'addon',
                        'text' => t('titles.day'),
                    ],
                ],
            ],
        ]);

		$option = Options::get('packages.financial.checkout_limits');

        if ($option) {
            $setting->setDataForm('financial_checkout_limits', [
                'price' => $option['price'],
                'currency' => $option['currency'],
                'period' => $option['period'] / 86400,
            ]);
        } else {
            $setting->setDataForm('financial_checkout_limits', [
                'currency' => $defaultCurrency->id,
            ]);
        }
	}
}
