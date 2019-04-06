<?php
namespace themes\clipone\views\financial\settings\banks\accounts;
use packages\userpanel\user;
use themes\clipone\{viewTrait, views\formTrait, navigation, navigation\menuItem, breadcrumb};
use packages\financial\{views\settings\banks\accounts\Add as account_add, Bank, Bank\Account, authorization, authentication};

class Add extends account_add {
	use viewTrait, formTrait;
	protected $multiUser = false;
	public function __beforeLoad(){
		$this->setTitle(t("packages.financial.banks.account.add"));
		navigation::active("settings/financial/bankaccounts");
		$this->addBodyClass("settings-banks-accounts");
		$this->addBodyClass("banks-accounts-add");
		$this->multiUser = (bool) authorization::childrenTypes();
		if ($user = $this->getDataForm("user")) {
			if ($user = user::byId($user)) {
				$this->setDataForm($user->getFullName(), "user_name");
			}
		} else {
			$this->setDataForm(authentication::getUser()->getFullName(), "user_name");
		}
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
