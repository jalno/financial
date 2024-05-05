<?php

namespace themes\clipone\views\financial\Settings\Currencies;

use packages\base\Translator;
use packages\financial\Views\Settings\Currencies\Delete as CurrenciesDelete;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Delete extends CurrenciesDelete
{
    use ViewTrait;
    use FormTrait;
    protected $currency;

    public function __beforeLoad()
    {
        $this->currency = $this->getCurrency();
        $this->setTitle([
            Translator::trans('settings.financial.currencies'),
            Translator::trans('settings.financial.currency.delete'),
        ]);
        Navigation::active('settings/financial/currencies');
        $this->addBodyClass('financial-settings');
        $this->addBodyClass('currencies-delete');
    }
}
