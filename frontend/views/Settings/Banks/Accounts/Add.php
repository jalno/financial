<?php
namespace themes\clipone\views\financial\Settings\Banks\Accounts;
use packages\userpanel\User;
use themes\clipone\{ViewTrait, Views\FormTrait, Navigation, Navigation\MenuItem, Breadcrumb};
use packages\financial\{Views\Settings\Banks\Accounts\Add as AccountAdd, Bank, Bank\Account, Authorization, Authentication};

class Add extends AccountAdd {
	use ViewTrait, FormTrait;
	protected $multiUser = false;
	public function __beforeLoad(){
		$this->setTitle(t("packages.financial.banks.account.add"));
		Navigation::active("settings/financial/bankaccounts");
		$this->addBodyClass("settings-banks-accounts");
		$this->addBodyClass("banks-accounts-add");
		$this->multiUser = (bool) Authorization::childrenTypes();
		if ($user = $this->getDataForm("user")) {
			if ($user = User::byId($user)) {
				$this->setDataForm($user->getFullName(), "user_name");
			}
		} else {
			$this->setDataForm(Authentication::getUser()->getFullName(), "user_name");
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
