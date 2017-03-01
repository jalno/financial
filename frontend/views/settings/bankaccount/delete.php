<?php
namespace themes\clipone\views\financial\settings\bankaccount;
use \packages\base\translator;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\formTrait;
use \packages\financial\views\settings\bankaccount\delete as bankAccountsDelete;
class delete extends bankAccountsDelete{
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
		navigation::active("settings/bankaccounts");
	}
}
