<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay\accept as acceptView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

class accept extends acceptView{
	use viewTrait,formTrait;
	protected $pay;
	protected $transaction;
	protected $action = 'accept';
	function __beforeLoad(){
		$this->pay = $this->getPay();
		$this->transaction = $this->pay->transaction;
		$this->setTitle(array(
			translator::trans('pay.byId', array('id' => $this->pay->id)),
			translator::trans('pay.accept')
		));
		$this->setShortDescription(translator::trans('pay.number',array('number' =>  $this->pay->id)));
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
		$item->setTitle(translator::trans('pay.accept'));
		$item->setURL(userpanel\url('transactions/pay/accept/'.$this->pay->id));
		$item->setIcon('fa fa-check');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
}
