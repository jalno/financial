<?php
namespace packages\financial\views\settings\banks\accounts;
use packages\financial\{views\listview, authorization};
use packages\base\views\traits\form as formTrait;

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
}
