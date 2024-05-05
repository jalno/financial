<?php
namespace themes\clipone\Views\Transactions;
use \packages\base\Translator;
use \packages\userpanel\User;
use \packages\financial\Views\Transactions\AddingCredit as TransactionsAddingCredit;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
use \themes\clipone\Navigation;
class AddingCredit extends TransactionsAddingCredit{
	use ViewTrait,FormTrait;
	function __beforeLoad(){
		$this->setTitle(array(
			Translator::trans('tranactions'),
			Translator::trans('transaction.adding_credit')
		));
		Navigation::active("transactions/list");
		$this->setUserInput();
	}
	private function setUserInput(){
		if($error = $this->getFromErrorsByInput('client')){
			$error->setInput('client_name');
			$this->setFormError($error);
		}
		$user = $this->getDataForm('client');
		if($user and $user = User::byId($user)){
			$this->setDataForm($user->getFullName(), 'client_name');
		}
	}
}
