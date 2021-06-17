<?php
namespace packages\financial\views\settings\banks\accounts;

use packages\base\{DB\DBObject, views\traits\form as formTrait};
use packages\financial\{views\listview, authorization};

class Search extends listview {
	use formTrait;
	public static function onSourceLoad(){
		self::$navigation = authorization::is_accessed("settings_banks_accounts_search");
	}
	protected static $navigation;
	protected $canEdit;
	protected $canDelete;
	protected $canAdd;
	public function __construct(){
		$this->canAdd = authorization::is_accessed("settings_banks_accounts_add");
		$this->canEdit = authorization::is_accessed("settings_banks_accounts_edit");
		$this->canDelete = authorization::is_accessed("settings_banks_accounts_delete");
	}
	public function getBankaccounts(){
		return $this->dataList;
	}
	public function export(): array {
		return array(
			'data' => array(
				'items' => DBObject::objectToArray($this->dataList, true),
				'items_per_page' => (int)$this->itemsPage,
				'current_page' => (int)$this->currentPage,
				'total_items' => (int)$this->totalItems,
			),
		);
	}
}
