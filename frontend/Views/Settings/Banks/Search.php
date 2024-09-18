<?php

namespace themes\clipone\Views\Financial\Settings\Banks;

use packages\base\Views\Traits\Form as BaseFormTrait;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\financial\Views\ListView;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Search extends ListView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;
    use BaseFormTrait;

    protected $canAdd;
    protected $canEdit;
    protected $canDelete;

    public function __beforeLoad()
    {
        $this->canAdd = Authorization::is_accessed('settings_banks_add');
        $this->canEdit = Authorization::is_accessed('settings_banks_edit');
        $this->canDelete = Authorization::is_accessed('settings_banks_delete');
        $this->setTitle(t('packages.financial.banks'));
        $this->setButtons();
        Navigation::active('settings/financial/banks');
        $this->addBodyClass('settings-banks');
    }

    public function setButtons()
    {
        $this->setButton('bank_edit', $this->canEdit, [
            'title' => t('packages.financial.edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-warning'],
            'data' => [
                'action' => 'edit',
            ],
        ]);
        $this->setButton('bank_delete', $this->canDelete, [
            'title' => t('packages.financial.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
            'data' => [
                'action' => 'delete',
            ],
        ]);
    }

    protected function getBanks(): array
    {
        return $this->getDataList();
    }

    protected function getStatusForSelect(): array
    {
        return [
            [
                'title' => t('packages.financial.choose'),
                'value' => '',
            ],
            [
                'title' => t('packages.financial.bank.status.Active'),
                'value' => Bank::Active,
            ],
            [
                'title' => t('packages.financial.bank.status.Deactive'),
                'value' => Bank::Deactive,
            ],
        ];
    }

    protected function getComparisonsForSelect()
    {
        return [
            [
                'title' => t('search.comparison.contains'),
                'value' => 'contains',
            ],
            [
                'title' => t('search.comparison.equals'),
                'value' => 'equals',
            ],
            [
                'title' => t('search.comparison.startswith'),
                'value' => 'startswith',
            ],
        ];
    }
}
