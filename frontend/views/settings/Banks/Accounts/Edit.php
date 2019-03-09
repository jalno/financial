<?php
namespace themes\clipone\views\financial\settings\banks\accounts;
use packages\userpanel\user;
use themes\clipone\{viewTrait, navigation, breadcrumb, views\formTrait, navigation\menuItem};
use packages\financial\{Bank, Bank\Account, views\settings\banks\accounts\Edit as account_edit, authorization, authentication};

class Edit extends account_edit{
	use viewTrait, formTrait;
	protected $account;
	protected $multiUser = false;
	protected $canAccept;
	public function __beforeLoad() {
		$this->account = $this->getBankaccount();
		$this->setTitle(t("packages.financial.banks.account.edit"));
		navigation::active("settings/financial/bankaccounts");
		$this->multiUser = (bool) authorization::childrenTypes();
		$this->addBodyClass("settings-banks-accounts");
		$this->addBodyClass("banks-accounts-edit");
		if ($user = $this->getDataForm("user")) {
			if ($user == $this->account->user_id) {
				$this->setDataForm($this->account->user->getFullName(), "user_name");
			} else if ($user = user::byId($user)) {
				$this->setDataForm($user->getFullName(), "user_name");
			}
		} else {
			$this->setDataForm(authentication::getUser()->getFullName(), "user_name");
		}
		$this->canAccept = authorization::is_accessed("settings_banks_accounts_accept");
	}
	protected function getBanksForSelect(): array {
		$banks = array();
		$items = (new Bank)
				->where("status", Bank::Active)
				->get();
		foreach ($items as $item) {
			$banks[] = array(
				"title" => $item->title,
				"value" => $item->id,
			);
		}
		return $banks;
	}
}
