<?php

namespace packages\financial\Views\Settings\GateWays;

use packages\financial\Bank;
use packages\financial\Events\GateWays;
use packages\userpanel\Views\Form;

class Add extends Form
{
    public function setGateways(GateWays $gateways)
    {
        $this->setData($gateways, 'gateways');
    }

    protected function getGateways()
    {
        return $this->getData('gateways');
    }

    public function setCurrencies(array $currencies)
    {
        $this->setData($currencies, 'currencies');
    }

    protected function getCurrencies(): array
    {
        return $this->getData('currencies');
    }

    protected function getAccounts(): array
    {
        $account = new Bank\Account();
        $account->where('status', Bank\Account::Active);

        return $account->get();
    }
}
