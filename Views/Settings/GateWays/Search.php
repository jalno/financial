<?php
namespace packages\financial\Views\Settings\GateWays;
use \packages\userpanel\Views\ListView;
use \packages\financial\Authorization;
use \packages\financial\Events\GateWays;
use \packages\base\Views\Traits\Form as FormTrait;
class Search extends ListView{
	use FormTrait;
	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	static protected $navigation;
	function __construct(){
		$this->canAdd = Authorization::is_accessed('settings_gateways_add');
		$this->canEdit = Authorization::is_accessed('settings_gateways_edit');
		$this->canDel = Authorization::is_accessed('settings_gateways_delete');
	}
	public function getGateways(){
		return $this->getData('gateways');
	}
	public function setGateways(gateways $gateways){
		$this->setData($gateways, 'gateways');
	}
	public static function onSourceLoad(){
		self::$navigation = Authorization::is_accessed('settings_gateways_search');
	}
}
