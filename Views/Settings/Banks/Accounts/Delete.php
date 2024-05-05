<?php

namespace packages\financial\Views\Settings\Banks\Accounts;

use packages\financial\Bank\Account;
use packages\financial\Views\Form;

class Delete extends Form
{
    public function setBankaccount(Account $account)
    {
        $this->setData($account, 'account');
    }

    protected function getBankaccount()
    {
        return $this->getData('account');
    }
}
