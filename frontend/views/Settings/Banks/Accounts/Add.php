<?php

namespace themes\clipone\Views\Financial\Settings\Banks\Accounts;

use packages\financial\Authentication;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\financial\Views\Settings\Banks\Accounts\Add as AccountAdd;
use packages\userpanel\User;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Add extends AccountAdd
{
    use ViewTrait;
    use FormTrait;
    protected $multiUser = false;

    public function __beforeLoad()
    {
        $this->setTitle(t('packages.financial.banks.account.add'));
        Navigation::active('settings/financial/bankaccounts');
        $this->addBodyClass('settings-banks-accounts');
        $this->addBodyClass('banks-accounts-add');
        $this->multiUser = (bool) Authorization::childrenTypes();
        if ($user = $this->getDataForm('user')) {
            if ($user = User::byId($user)) {
                $this->setDataForm($user->getFullName(), 'user_name');
            }
        } else {
            $this->setDataForm(Authentication::getUser()->getFullName(), 'user_name');
        }
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
