<?php
namespace themes\clipone\views\financial\settings\banks\accounts;
use themes\clipone\{viewTrait, views\formTrait, navigation};
use packages\financial\views\settings\banks\accounts\Delete as bankAccountsDelete;

class Delete extends bankAccountsDelete{
	use viewTrait, formTrait;
	protected $account;
	public function __beforeLoad(){
		$this->account = $this->getBankaccount();
		$this->setTitle(t("packages.financial.banks.account.delete"));
		navigation::active("settings/financial/bankaccounts");
	}
}
