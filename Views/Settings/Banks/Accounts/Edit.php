<?php
namespace packages\financial\Views\Settings\Banks\Accounts;
use packages\financial\Views\Form;
use packages\financial\Bank\Account;

class Edit extends Form{
	public function setBankaccount(Account $account){
		$this->setData($account, "account");
		$this->setDataForm($account->toArray());
	}
	protected function getBankaccount() {
		return $this->getData("account");
	}
}
