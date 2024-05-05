<?php
namespace packages\financial\Views\Settings\Currencies;
use \packages\userpanel\Views\ListView;
use \packages\financial\Authorization;
use \packages\financial\Events\Currencies;
use \packages\base\Views\Traits\Form as FormTrait;
class Search extends ListView{
	use FormTrait;
	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	static protected $navigation;
	function __construct(){
		$this->canAdd = Authorization::is_accessed('settings_currencies_add');
		$this->canEdit = Authorization::is_accessed('settings_currencies_edit');
		$this->canDel = Authorization::is_accessed('settings_currencies_delete');
	}
	public function getCurrencies():array{
		return $this->getDataList();
	}
	public static function onSourceLoad(){
		self::$navigation = Authorization::is_accessed('settings_currencies_search');
	}
}
