<?php
namespace themes\clipone\views\financial\Settings\Banks\Accounts;
use themes\clipone\{ViewTrait, Views\FormTrait, Navigation};
use packages\financial\Views\Settings\Banks\Accounts\Delete as BankAccountsDelete;

class Delete extends BankAccountsDelete{
	use ViewTrait, FormTrait;
	protected $account;
	public function __beforeLoad(){
		$this->account = $this->getBankaccount();
		$this->setTitle(t("packages.financial.banks.account.delete"));
		Navigation::active("settings/financial/bankaccounts");
	}
}
