<?php
namespace packages\financial\views\settings\banks\accounts;
use packages\financial\views\form;
use packages\financial\Bank\Account;

class Edit extends form{
	public function setBankaccount(Account $account){
		$this->setData($account, "account");
		$this->setDataForm($account->toArray());
	}
	protected function getBankaccount() {
		return $this->getData("account");
	}
}
