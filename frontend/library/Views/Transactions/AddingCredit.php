<?php

namespace themes\clipone\Views\Transactions;

use packages\base\Translator;
use packages\financial\Views\Transactions\AddingCredit as TransactionsAddingCredit;
use packages\userpanel\User;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class AddingCredit extends TransactionsAddingCredit
{
    use ViewTrait;
    use FormTrait;

    public function __beforeLoad()
    {
        $this->setTitle([
            t('tranactions'),
            t('transaction.adding_credit'),
        ]);
        Navigation::active('transactions/list');
        $this->setUserInput();
    }

    private function setUserInput()
    {
        if ($error = $this->getFromErrorsByInput('client')) {
            $error->setInput('client_name');
            $this->setFormError($error);
        }
        $user = $this->getDataForm('client');
        if ($user and $user = User::byId($user)) {
            $this->setDataForm($user->getFullName(), 'client_name');
        }
    }
}
