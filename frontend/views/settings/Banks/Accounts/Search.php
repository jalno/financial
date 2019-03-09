<?php
namespace themes\clipone\views\financial\settings\banks\accounts;
use packages\userpanel;
use themes\clipone\{viewTrait, views\formTrait, views\listTrait, navigation, navigation\menuItem};
use packages\financial\{views\settings\banks\accounts\Search as accounts_list, authorization, Bank\Account};

class Search extends accounts_list{
	use viewTrait, listTrait, formTrait;
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if (parent::$navigation) {
			$settings = navigation::getByName("settings");
			if (!$financial = navigation::getByName("settings/financial")) {
				$financial = new menuItem("financial");
				$financial->setTitle(t("settings.financial"));
				$financial->setIcon("fa fa-money");
				if($settings) $settings->addItem($financial);
			}
			$bankaccount = new menuItem("bankaccounts");
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
		navigation::active("settings/financial/bankaccounts");
		$this->multiUser = (bool) authorization::childrenTypes();
		$this->canAccept = authorization::is_accessed("settings_banks_accounts_accept");
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
