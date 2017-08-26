<?php
namespace packages\financial\views\settings\currencies;
use \packages\userpanel\views\listview;
use \packages\financial\authorization;
use \packages\financial\events\currencies;
use \packages\base\views\traits\form as formTrait;
class search extends listview{
	use formTrait;
	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	static protected $navigation;
	function __construct(){
		$this->canAdd = authorization::is_accessed('settings_currencies_add');
		$this->canEdit = authorization::is_accessed('settings_currencies_edit');
		$this->canDel = authorization::is_accessed('settings_currencies_delete');
	}
	public function getCurrencies():array{
		return $this->getDataList();
	}
	public static function onSourceLoad(){
		self::$navigation = authorization::is_accessed('settings_currencies_search');
	}
}
