<?php
namespace themes\clipone\views\settings\bankaccount;
use \packages\base\translator;

use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\views\formTrait;


use \packages\financial\views\settings\bankaccount\delete as accounts_delete;

class delete extends accounts_delete{
	use viewTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('settings'),
			translator::trans('bankaccounts'),
			translator::trans('bankaccount_delete')
		));
		$this->setNavigation();
	}
	private function setNavigation(){
		breadcrumb::addItem(navigation::getByName('settings'));
		breadcrumb::addItem(navigation::getByName('settings/bankaccounts'));
		$item = new menuItem("delete");
		$item->setTitle(translator::trans('bankaccount_delete'));
		$item->setIcon('fa fa-trash-o');
		breadcrumb::addItem($item);
		navigation::active("settings/bankaccounts");
	}
}
