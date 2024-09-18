<?php

namespace packages\financial\Views\Settings\Currencies;

use packages\base\Views\Traits\Form as FormTrait;
use packages\financial\Authorization;
use packages\userpanel\Views\ListView;

class Search extends ListView
{
    use FormTrait;
    protected $canAdd;
    protected $canEdit;
    protected $canDel;

    public function __construct()
    {
        $this->canAdd = Authorization::is_accessed('settings_currencies_add');
        $this->canEdit = Authorization::is_accessed('settings_currencies_edit');
        $this->canDel = Authorization::is_accessed('settings_currencies_delete');
    }

    public function getCurrencies(): array
    {
        return $this->getDataList();
    }
}
