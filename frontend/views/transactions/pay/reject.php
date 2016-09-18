<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay\reject as rejectView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\views\formTrait;

class reject extends rejectView{
	use formTrait;
	protected $pay;
	protected $transaction;
	protected $action = 'reject';
	function __beforeLoad(){
		$this->pay = $this->getPay();
		$this->transaction = $this->pay->transaction;
		$this->setTitle(array(
			translator::trans('pay.byId', array('id' => $this->pay->id)),
			translator::trans('pay.reject')
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
		$item->setTitle(translator::trans('pay.reject'));
		$item->setURL(userpanel\url('transactions/pay/reject/'.$this->pay->id));
		$item->setIcon('fa fa-check');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
}
