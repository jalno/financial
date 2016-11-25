<?php
namespace themes\clipone\views\transactions\pay\onlinepay;
use \packages\base\translator;
use \packages\base\frontend\theme;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay\onlinepay\redirect as redirectView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

class redirect extends redirectView{
	use viewTrait,formTrait;
	protected $transaction;
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->setTitle(translator::trans('pay.redirect'));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addJSFile(theme::url('assets/js/pages/transactions.pay.onlinepay.redirect.js'));
	}
	protected function createFormData(){
		foreach($this->getRedirect()->data as $key => $value){
			echo $this->createField(array(
				'type' => 'hidden',
				'name' => $key,
				'value' => $value
			));
		}
	}
	private function setNavigation(){
		$item = new menuItem("transactions");
		$item->setTitle(translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction");
		$item->setTitle(translator::trans('tranaction', array('id' => $this->transaction->id)));
		$item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
		$item->setIcon('clip-user');
		breadcrumb::addItem($item);

		$item = new menuItem("pay");
		$item->setTitle(translator::trans('pay'));
		$item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		breadcrumb::addItem($item);

		$item = new menuItem("onlinepay");
		$item->setTitle(translator::trans('pay.method.onlinepay'));
		$item->setURL(userpanel\url('transactions/pay/onlinepay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
}
