<?php

namespace themes\clipone\Views\Financial\Settings\GateWays;

use packages\base\Options;
use packages\base\Translator;
use packages\financial\PayPort as GateWay;
use packages\financial\Views\Settings\GateWays\Edit as EditView;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Edit extends EditView
{
    use ViewTrait;
    use FormTrait;

    public function __beforeLoad()
    {
        $this->setTitle(Translator::trans('settings.financial.gateways.edit'));
        $this->setNavigation();
        $this->addBodyClass('transaction-settings-gateway');
    }

    private function setNavigation()
    {
        Navigation::active('settings/financial/gateways');
    }

    public function getGatewaysForSelect()
    {
        $options = [];
        foreach ($this->getGateways() as $gateway) {
            $title = Translator::trans('financial.gateway.'.$gateway->getName());
            $options[] = [
                'value' => $gateway->getName(),
                'title' => $title ? $title : $gateway->getName(),
            ];
        }

        return $options;
    }

    public function getGatewayStatusForSelect()
    {
        $options = [
            [
                'title' => Translator::trans('financial.gateway.status.active'),
                'value' => GateWay::active,
            ],
            [
                'title' => Translator::trans('financial.gateway.status.deactive'),
                'value' => GateWay::deactive,
            ],
        ];

        return $options;
    }

    protected function getNumbersData()
    {
        $numbers = [];
        foreach ($this->getGateway()->numbers as $number) {
            $numberData = $number->toArray();
            if (Options::get('packages.financial.defaultNumber') == $number->id) {
                $numberData['primary'] = true;
            }
            $numbers[] = $numberData;
        }

        return $numbers;
    }

    protected function getCurrenciesForSelect(): array
    {
        $currencies = [];
        foreach ($this->getCurrencies() as $currency) {
            $currencies[] = [
                'label' => $currency->title,
                'value' => $currency->id,
            ];
        }

        return $currencies;
    }

    protected function getAccountsForSelect(): array
    {
        $accounts = [
            [
                'title' => 'هیچ کدام',
                'value' => '',
            ],
        ];
        foreach ($this->getAccounts() as $account) {
            $accounts[] = [
                'title' => $account->title.' - '.$account->shaba,
                'value' => $account->id,
            ];
        }

        return $accounts;
    }
}
