<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel\user;
use \packages\financial\views\transactions\addingcredit as transactionsAddingCredit;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation;
class addingcredit extends transactionsAddingCredit{
	use viewTrait,formTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('tranactions'),
			translator::trans('transaction.adding_credit')
		));
		navigation::active("transactions/list");
		$this->setUserInput();
	}
	private function setUserInput(){
		if($error = $this->getFromErrorsByInput('client')){
			$error->setInput('client_name');
			$this->setFormError($error);
		}
		$user = $this->getDataForm('client');
		if($user and $user = user::byId($user)){
			$this->setDataForm($user->getFullName(), 'client_name');
		}
	}
}
