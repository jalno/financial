<?php

namespace packages\financial\listeners\userpanel;

use packages\base\Options;
use packages\financial\Authorization;
use packages\financial\controllers\userpanel\Settings as Controller;
use packages\financial\Currency;
use packages\financial\validators\CheckoutLimitValidator;
use packages\userpanel\events\Settings;
use packages\userpanel\User;

class SettingsListener
{
    private array $currencies = [];
    private ?User $user = null;

    public function __construct()
    {
        $this->currencies = Currency::get(null, ['id', 'title']);
    }

    public function settings_list(Settings $settings): void
    {
        $this->user = $settings->getUser();

        $tuning = new Settings\Tuning('financial', 'fa fa-money');
        $tuning->setController(controller::class);

        $this->addChangeCurrencyFields($tuning);
        $this->addChangeCheckoutLimitFields($tuning);

        if (!empty($tuning->getInputs())) {
            $settings->addTuning($tuning);
        }
    }

    private function addChangeCurrencyFields(Settings\Tuning $tuning)
    {
        if (!Authorization::is_accessed('profile_change_currency')) {
            return;
        }

        $tuning->addInput([
            'name' => 'financial_transaction_currency',
            'type' => 'number',
            'values' => $this->getCurrencyForCheck(),
        ]);

        $tuning->addField([
            'name' => 'financial_transaction_currency',
            'type' => 'select',
            'label' => t('financial.usersettings.transaction.currency'),
            'options' => $this->getCurrencyForSelect(),
        ]);

        $defaultCurrency = Currency::getDefault($this->user);
        if ($defaultCurrency) {
            $tuning->setDataForm('financial_transaction_currency', $defaultCurrency->id);
        }
    }

    private function addChangeCheckoutLimitFields(Settings\Tuning $tuning)
    {
        if (!Authorization::is_accessed('profile_checkout_limits')) {
            return;
        }

        $defaultCurrency = Currency::getDefault();

        $tuning->addInput([
            'name' => 'financial_checkout_limits',
            'type' => CheckoutLimitValidator::class,
            'optional' => true,
        ]);

        $tuning->addField([
            'name' => 'financial_checkout_limits[price]',
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

        $tuning->addField([
            'name' => 'financial_checkout_limits[currency]',
            'type' => 'hidden',
        ]);

        $tuning->addField([
            'name' => 'financial_checkout_limits[period]',
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

        $option = $this->user->option('financial_checkout_limits');

        if (!$option) {
            $option = Options::get('packages.financial.checkout_limits');
        }

        if ($option) {
            $tuning->setDataForm('financial_checkout_limits', [
                'price' => $option['price'],
                'currency' => $option['currency'],
                'period' => $option['period'] / 86400,
            ]);
        } else {
            $tuning->setDataForm('financial_checkout_limits', [
                'currency' => $defaultCurrency->id,
            ]);
        }
    }

    private function getCurrencyForSelect(): array
    {
        return array_map(function (Currency $currency): array {
            return [
                'title' => $currency->title,
                'value' => $currency->id,
            ];
        }, $this->currencies);
    }

    private function getCurrencyForCheck(): array
    {
        return array_column($this->currencies, 'id');
    }
}
