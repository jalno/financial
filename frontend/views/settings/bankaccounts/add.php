<?php
namespace themes\clipone\views\settings\bankaccount;
use \packages\base\translator;

use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\breadcrumb;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation\menuItem;

use \packages\financial\bankaccount;
use \packages\financial\views\settings\bankaccount\add as account_add;

class add extends account_add{
	use viewTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('settings'),
			translator::trans('bankaccounts'),
			translator::trans('bankaccount_add')
		));
		$this->setNavigation();
	}
	private function setNavigation(){
		breadcrumb::addItem(navigation::getByName('settings'));
		breadcrumb::addItem(navigation::getByName('settings/bankaccounts'));
		$item = new menuItem("add");
		$item->setTitle(translator::trans('bankaccount_add'));
		$item->setIcon('fa fa-plus');
		breadcrumb::addItem($item);
		navigation::active("settings/bankaccounts");
	}
	protected function setStatusForSelect(){
		return array(
			array(
				'title' => translator::trans("bankaccount.active"),
				'value' => bankaccount::active
			),
			array(
				'title' => translator::trans("bankaccount.deactive"),
				'value' => bankaccount::deactive
			)
		);
	}
}
