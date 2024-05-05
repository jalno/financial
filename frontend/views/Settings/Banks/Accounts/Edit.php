<?php

namespace themes\clipone\views\financial\Settings\Banks\Accounts;

use packages\financial\Authentication;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\financial\Views\Settings\Banks\Accounts\Edit as AccountEdit;
use packages\userpanel\User;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Edit extends AccountEdit
{
    use ViewTrait;
    use FormTrait;
    protected $account;
    protected $multiUser = false;
    protected $canAccept;

    public function __beforeLoad()
    {
        $this->account = $this->getBankaccount();
        $this->setTitle(t('packages.financial.banks.account.edit'));
        Navigation::active('settings/financial/bankaccounts');
        $this->multiUser = (bool) Authorization::childrenTypes();
        $this->addBodyClass('settings-banks-accounts');
        $this->addBodyClass('banks-accounts-edit');
        if ($user = $this->getDataForm('user')) {
            if ($user == $this->account->user_id) {
                $this->setDataForm($this->account->user->getFullName(), 'user_name');
            } elseif ($user = User::byId($user)) {
                $this->setDataForm($user->getFullName(), 'user_name');
            }
        } else {
            $this->setDataForm(Authentication::getUser()->getFullName(), 'user_name');
        }
        $this->canAccept = Authorization::is_accessed('settings_banks_accounts_accept');
    }

    protected function getBanksForSelect(): array
    {
        $banks = [];
        $items = (new Bank())
                ->where('status', Bank::Active)
                ->get();
        foreach ($items as $item) {
            $banks[] = [
                'title' => $item->title,
                'value' => $item->id,
            ];
        }

        return $banks;
    }
}
