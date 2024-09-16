<?php

namespace packages\financial\Views\Settings\GateWays;

use packages\base\Views\Traits\Form as FormTrait;
use packages\financial\Authorization;
use packages\financial\Events\GateWays;
use packages\userpanel\Views\ListView;

class Search extends ListView
{
    use FormTrait;
    protected $canAdd;
    protected $canEdit;
    protected $canDel;

    public function __construct()
    {
        $this->canAdd = Authorization::is_accessed('settings_gateways_add');
        $this->canEdit = Authorization::is_accessed('settings_gateways_edit');
        $this->canDel = Authorization::is_accessed('settings_gateways_delete');
    }

    public function getGateways()
    {
        return $this->getData('gateways');
    }

    public function setGateways(GateWays $gateways)
    {
        $this->setData($gateways, 'gateways');
    }
}
