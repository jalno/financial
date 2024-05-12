<?php

namespace themes\clipone\Views\Financial\Settings\Currencies;

use packages\financial\Views\Settings\Currencies\Edit as CurrenciesEdit;
use packages\financial\{Currency};
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Edit extends CurrenciesEdit
{
    use ViewTrait;
    use FormTrait;

    protected $currency;
    protected $hasRate;
    private $currencies;

    public function __beforeLoad(): void
    {
        $this->currency = $this->getCurrency();
        $this->currencies = $this->getCurrencies();
        $this->hasRate = $this->currency->hasRate();
        $this->setTitle([
            t('settings.financial.currencies'),
            t('settings.financial.currency.edit'),
        ]);
        Navigation::active('settings/financial/currencies');
        $this->addBodyClass('financial-settings');
        $this->addBodyClass('currencies-edit');
    }

    protected function geCurrenciesForSelect(): array
    {
        $currencies = [];
        foreach ($this->currencies as $currency) {
            $currencies[] = [
                'title' => $currency->title,
                'value' => $currency->id,
            ];
        }

        return $currencies;
    }

    protected function getRoundingBehavioursForSelect(): array
    {
        return [
            [
                'title' => t('financial.setting.currency.rounding_behaviour.ceil'),
                'value' => Currency::CEIL,
            ],
            [
                'title' => t('financial.setting.currency.rounding_behaviour.round'),
                'value' => Currency::ROUND,
            ],
            [
                'title' => t('financial.setting.currency.rounding_behaviour.floor'),
                'value' => Currency::FLOOR,
            ],
        ];
    }
}
