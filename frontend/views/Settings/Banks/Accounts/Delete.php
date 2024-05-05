<?php

namespace themes\clipone\views\financial\Settings\Banks\Accounts;

use packages\financial\Views\Settings\Banks\Accounts\Delete as BankAccountsDelete;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Delete extends BankAccountsDelete
{
    use ViewTrait;
    use FormTrait;
    protected $account;

    public function __beforeLoad()
    {
        $this->account = $this->getBankaccount();
        $this->setTitle(t('packages.financial.banks.account.delete'));
        Navigation::active('settings/financial/bankaccounts');
    }
}
