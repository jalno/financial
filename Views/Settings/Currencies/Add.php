<?php

namespace packages\financial\Views\Settings\Currencies;

use packages\userpanel\Views\Form;

trait CurrenciesTrait
{
    public function setCurrencies(array $currencies)
    {
        $this->setData($currencies, 'currencies');
    }

    protected function getCurrencies(): array
    {
        return $this->getData('currencies');
    }
}
class Add extends Form
{
    use CurrenciesTrait;
}
