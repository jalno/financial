<?php

namespace packages\financial\Views\Settings\GateWays;

use packages\financial\PayPort;
use packages\userpanel\Views\Form;

class Delete extends Form
{
    public function setGateway(PayPort $gateway)
    {
        $this->setData($gateway, 'gateway');
    }

    protected function getGateway()
    {
        return $this->getData('gateway');
    }
}
