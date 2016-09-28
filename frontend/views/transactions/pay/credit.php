<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay\credit as creditView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

class credit extends creditView{
	use viewTrait,formTrait;
	protected $transaction;
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			translator::trans('pay.byCredit')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();

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

		$item = new menuItem("credit");
		$item->setTitle(translator::trans('pay.byCredit'));
		$item->setURL(userpanel\url('transactions/pay/credit/'.$this->transaction->id));
		$item->setIcon('clip-phone-3');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
}
