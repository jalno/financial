<?php
namespace themes\clipone\views\financial\Settings\Banks\Accounts;
use packages\userpanel;
use themes\clipone\{ViewTrait, Views\FormTrait, Views\ListTrait, Navigation, Navigation\MenuItem};
use packages\financial\{Views\Settings\Banks\Accounts\Search as AccountsList, Authorization, Bank\Account};

class Search extends AccountsList{
	use ViewTrait, ListTrait, FormTrait;
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if (parent::$navigation) {
			$settings = Navigation::getByName("settings");
			if (!$financial = Navigation::getByName("settings/financial")) {
				$financial = new MenuItem("financial");
				$financial->setTitle(t("settings.financial"));
				$financial->setIcon("fa fa-money");
				if($settings) $settings->addItem($financial);
			}
			$bankaccount = new MenuItem("bankaccounts");
			$bankaccount->setTitle(t("packages.financial.banks.accounts"));
			$bankaccount->setURL(userpanel\url("settings/financial/banks/accounts"));
			$bankaccount->setIcon("fa fa-credit-card");
			$financial->addItem($bankaccount);
		}
	}
	protected $multiUser = false;
	protected $canAccept = false;
	public function __beforeLoad(){
		$this->setTitle(t("packages.financial.banks.accounts"));
		$this->setButtons();
		Navigation::active("settings/financial/bankaccounts");
		$this->multiUser = (bool) Authorization::childrenTypes();
		$this->canAccept = Authorization::is_accessed("settings_banks_accounts_accept");
		$this->addBodyClass("settings-banks-accounts");
	}
	public function setButtons() {
		$this->setButton("edit", $this->canEdit, array(
			"title" => t("packages.financial.edit"),
			"icon" => "fa fa-edit",
			"classes" => array("btn", "btn-xs", "btn-teal")
		));
		$this->setButton("delete", $this->canDelete, array(
			"title" => t("packages.financial.delete"),
			"icon" => "fa fa-times",
			"classes" => array("btn", "btn-xs", "btn-bricky")
		));
	}
	protected function getStatusForSelect(): array {
		return array(
			array(
				"title" => t("packages.financial.choose"),
				"value" => ""
			),
			array(
				"title" => t("packages.financial.banks.account.status.Active"),
				"value" => Account::Active
			),
			array(
				"title" => t("packages.financial.banks.account.status.WaitForAccept"),
				"value" => Account::WaitForAccept
			),
			array(
				"title" => t("packages.financial.banks.account.status.Deactive"),
				"value" => Account::Deactive
			),
			array(
				"title" => t("packages.financial.banks.account.status.Rejected"),
				"value" => Account::Rejected
			),
		);
	}
	protected function getComparisonsForSelect(): array {
		return array(
			array(
				"title" => t("search.comparison.contains"),
				"value" => "contains"
			),
			array(
				"title" => t("search.comparison.equals"),
				"value" => "equals"
			),
			array(
				"title" => t("search.comparison.startswith"),
				"value" => "startswith"
			)
		);
	}
}
