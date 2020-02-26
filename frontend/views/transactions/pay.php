<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay as payView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;

class pay extends payView{
	use viewTrait;
	protected $transaction;
	protected $methods = array();
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->methods = $this->getMethods();
		$this->setTitle(array(
			translator::trans('title.transaction.view')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay");
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
		$item->setIcon('fa fa-television');
		breadcrumb::addItem($item);

		$item = new menuItem("pay");
		$item->setTitle(translator::trans('pay'));
		$item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
	protected function getColumnWidth(){
		return ($this->canViewGuestLink ? 12 : 6)/count($this->methods);
	}
}
