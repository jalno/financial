<?php
namespace themes\clipone\views\financial\settings\Banks;
use packages\userpanel;
use packages\financial\{views\listview, authorization, Bank};
use packages\base\views\traits\form as baseFormTrait;
use themes\clipone\{viewTrait, views\listTrait, views\formTrait, navigation, navigation\menuItem};
class Search extends listview {
	use viewTrait, listTrait, formTrait, baseFormTrait;
	public static function onSourceLoad() {
		if (authorization::is_accessed("settings_banks_search")) {
			if ($settings = navigation::getByName("settings")) {
				if (!$financial = navigation::getByName("settings/financial")) {
					$financial = new menuItem("financial");
					$financial->setTitle(t("settings.financial"));
					$financial->setIcon("fa fa-money");
					if ($settings) $settings->addItem($financial);
				}
				$bank = new menuItem("banks");
				$bank->setTitle(t("packages.financial.banks"));
				$bank->setURL(userpanel\url("settings/financial/banks"));
				$bank->setIcon("fa fa-university");
				$financial->addItem($bank);
			}
		}
	}
	protected $canAdd;
	protected $canEdit;
	protected $canDelete;
	public function __beforeLoad() {
		$this->canAdd = authorization::is_accessed("settings_banks_add");
		$this->canEdit = authorization::is_accessed("settings_banks_edit");
		$this->canDelete = authorization::is_accessed("settings_banks_delete");
		$this->setTitle(t("packages.financial.banks"));
		$this->setButtons();
		navigation::active("settings/financial/banks");
		$this->addBodyClass("settings-banks");
	}
	public function setButtons() {
		$this->setButton("bank_edit", $this->canEdit, array(
			"title" => t("packages.financial.edit"),
			"icon" => "fa fa-edit",
			"classes" => array("btn", "btn-xs", "btn-warning"),
			"data" => array(
				"action" => "edit",
			),
		));
		$this->setButton("bank_delete", $this->canDelete, array(
			"title" => t("packages.financial.delete"),
			"icon" => "fa fa-times",
			"classes" => array("btn", "btn-xs", "btn-bricky"),
			"data" => array(
				"action" => "delete",
			),
		));
	}
	protected function getBanks(): array {
		return $this->getDataList();
	}
	protected function getStatusForSelect(): array {
		return array(
			array(
				"title" => t("packages.financial.choose"),
				"value" => "",
			),
			array(
				"title" => t("packages.financial.bank.status.Active"),
				"value" => Bank::Active,
			),
			array(
				"title" => t("packages.financial.bank.status.Deactive"),
				"value" => Bank::Deactive,
			),
		);
	}
	protected function getComparisonsForSelect(){
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
