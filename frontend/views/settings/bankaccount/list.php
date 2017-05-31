<?php
namespace themes\clipone\views\financial\settings\bankaccount;
use \packages\base\translator;
use \packages\base\view\error;
use \packages\userpanel;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation\menuItem;
use \packages\financial\views\settings\bankaccount\listview as accounts_list;
class listview extends accounts_list{
	use viewTrait, listTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('settings'),
			translator::trans('bankaccounts'),
			translator::trans('list')
		));
		$this->setButtons();
		navigation::active("settings/financial/bankaccounts");
		if(empty($this->getBankaccounts())){
			$this->addNotFoundError();
		}
	}
	private function addNotFoundError(){
		$error = new error();
		$error->setType(error::NOTICE);
		$error->setCode('financial.settings.bankaccount.notfound');
		if($this->canAdd){
			$error->setData([
				[
					'type' => 'btn-success',
					'txt' => translator::trans('bankaccount_add'),
					'link' => userpanel\url('settings/financial/bankaccounts/add')
				]
			], 'btns');
		}
		$this->addError($error);
	}
	public function setButtons(){
		$this->setButton('edit', $this->canEdit, array(
			'title' => translator::trans('usertype.edit'),
			'icon' => 'fa fa-edit',
			'classes' => array('btn', 'btn-xs', 'btn-warning')
		));
		$this->setButton('delete', $this->canDelete, array(
			'title' => translator::trans('usertype.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky')
		));
	}
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if(parent::$navigation){
			$settings = navigation::getByName("settings");
			if(!$financial = navigation::getByName("settings/financial")){
				$financial = new menuItem("financial");
				$financial->setTitle(translator::trans('settings.financial'));
				$financial->setIcon("fa fa-money");
				if($settings)$settings->addItem($financial);
			}
			$bankaccount = new menuItem("bankaccounts");
			$bankaccount->setTitle(translator::trans("bankaccounts"));
			$bankaccount->setURL(userpanel\url('settings/financial/bankaccounts'));
			$financial->addItem($bankaccount);
		}
	}
}
