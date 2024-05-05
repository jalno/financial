<?php

namespace themes\clipone\views\financial\Settings\GateWays;

use packages\financial\Views\Settings\GateWays\Delete as DeleteView;
use themes\clipone\Navigation;
use themes\clipone\ViewTrait;

class Delete extends DeleteView
{
    use ViewTrait;

    public function __beforeLoad()
    {
        $this->setTitle(t('settings.financial.gateways.delete'));
        Navigation::active('settings/financial/gateways');
    }
}
