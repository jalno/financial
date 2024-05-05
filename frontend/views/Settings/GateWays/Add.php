<?php

namespace themes\clipone\views\financial\Settings\GateWays;

use packages\base\Frontend\Theme;
use packages\financial\PayPort as GateWay;
use packages\financial\Views\Settings\GateWays\Add as AddView;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Add extends AddView
{
    use ViewTrait;
    use FormTrait;

    public function __beforeLoad()
    {
        $this->setTitle(t('settings.financial.gateways.add'));
        $this->setNavigation();
        $this->addBodyClass('transaction-settings-gateway');
    }

    public function addAssets()
    {
        $this->addJSFile(Theme::url('assets/plugins/jquery-validation/dist/jquery.validate.min.js'));
        $this->addJSFile(Theme::url('assets/plugins/bootstrap-inputmsg/bootstrap-inputmsg.min.js'));
        $this->addJSFile(Theme::url('assets/js/pages/GateWays.js'));
    }

    private function setNavigation()
    {
        Navigation::active('settings/financial/gateways');
    }

    public function getGatewaysForSelect()
    {
        $options = [];
        foreach ($this->getGateways()->get() as $gateway) {
            $title = t('financial.gateway.'.$gateway->getName());
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
                'title' => t('financial.gateway.status.active'),
                'value' => GateWay::active,
            ],
            [
                'title' => t('financial.gateway.status.deactive'),
                'value' => GateWay::deactive,
            ],
        ];

        return $options;
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
                'title' => t('select.none'),
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
