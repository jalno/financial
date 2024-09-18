<?php

namespace packages\financial\Views\Settings\Banks\Accounts;

use packages\base\DB\DBObject;
use packages\base\Views\Traits\Form as FormTrait;
use packages\financial\Authorization;
use packages\financial\Views\ListView;

class Search extends ListView
{
    use FormTrait;

    protected $canEdit;
    protected $canDelete;
    protected $canAdd;

    public function __construct()
    {
        $this->canAdd = Authorization::is_accessed('settings_banks_accounts_add');
        $this->canEdit = Authorization::is_accessed('settings_banks_accounts_edit');
        $this->canDelete = Authorization::is_accessed('settings_banks_accounts_delete');
    }

    public function getBankaccounts()
    {
        return $this->dataList;
    }

    public function export(): array
    {
        return [
            'data' => [
                'items' => DBObject::objectToArray($this->dataList, true),
                'items_per_page' => (int) $this->itemsPage,
                'current_page' => (int) $this->currentPage,
                'total_items' => (int) $this->totalItems,
            ],
        ];
    }
}
