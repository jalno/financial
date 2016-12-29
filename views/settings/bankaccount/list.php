<?php
namespace packages\financial\views\settings\bankaccount;
use \packages\financial\views\listview as list_view;
use \packages\financial\authorization;
use \packages\base\views\traits\form as formTrait;
class listview extends  list_view{
	use formTrait;
	protected $canEdit;
	protected $canDelete;
	protected $canAdd;
	static protected $navigation;
	function __construct(){
		$this->canEdit = authorization::is_accessed('settings_bankaccounts_edit');
		$this->canDelete = authorization::is_accessed('settings_bankaccounts_delete');
		$this->canAdd = authorization::is_accessed('settings_bankaccounts_add');
	}
	static function onSourceLoad(){
		self::$navigation = authorization::is_accessed('settings_bankaccounts_list');
	}
	public function getBankaccounts(){
		return $this->dataList;
	}
}
