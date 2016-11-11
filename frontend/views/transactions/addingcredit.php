<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\base\frontend\theme;

use \packages\userpanel;
use \packages\userpanel\user;

use \packages\financial\views\transactions\addingcredit as transactionsAddingCredit;
use \packages\financial\transaction;

use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
class addingcredit extends transactionsAddingCredit{
	use viewTrait,formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('tranactions'),
			translator::trans('transaction.adding_credit')
		));
		navigation::active("transactions/list");
		$this->addAssets();
		$this->setUserInput();
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.addingcredit.js'));
	}
	private function setUserInput(){
		if($error = $this->getFromErrorsByInput('client')){
			$error->setInput('user_name');
			$this->setFormError($error);
		}
		$user = $this->getDataForm('client');
		if($user and $user = user::byId($user)){
			$this->setDataForm($user->name, 'user_name');
		}
	}
}
