<?php

namespace themes\clipone\Views\Financial\Settings\Banks\Accounts;

use packages\financial\Authorization;
use packages\financial\Bank\Account;
use packages\financial\Views\Settings\Banks\Accounts\Search as AccountsList;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Search extends AccountsList
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;

    protected $multiUser = false;
    protected $canAccept = false;

    public function __beforeLoad()
    {
        $this->setTitle(t('packages.financial.banks.accounts'));
        $this->setButtons();
        Navigation::active('settings/financial/bankaccounts');
        $this->multiUser = (bool) Authorization::childrenTypes();
        $this->canAccept = Authorization::is_accessed('settings_banks_accounts_accept');
        $this->addBodyClass('settings-banks-accounts');
    }

    public function setButtons()
    {
        $this->setButton('edit', $this->canEdit, [
            'title' => t('packages.financial.edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-teal'],
        ]);
        $this->setButton('delete', $this->canDelete, [
            'title' => t('packages.financial.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }

    protected function getStatusForSelect(): array
    {
        return [
            [
                'title' => t('packages.financial.choose'),
                'value' => '',
            ],
            [
                'title' => t('packages.financial.banks.account.status.Active'),
                'value' => Account::Active,
            ],
            [
                'title' => t('packages.financial.banks.account.status.WaitForAccept'),
                'value' => Account::WaitForAccept,
            ],
            [
                'title' => t('packages.financial.banks.account.status.Deactive'),
                'value' => Account::Deactive,
            ],
            [
                'title' => t('packages.financial.banks.account.status.Rejected'),
                'value' => Account::Rejected,
            ],
        ];
    }

    protected function getComparisonsForSelect(): array
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
