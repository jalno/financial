<?php
namespace themes\clipone\Views\Transactions\Pay;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\userpanel\Date;
use \packages\financial\Views\Transactions\Pay\Accept as AcceptView;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;

class Accept extends AcceptView{
	use ViewTrait,FormTrait;
	protected $pay;
	protected $transaction;
	protected $action = 'accept';
	function __beforeLoad(){
		$this->pay = $this->getPay();
		$this->transaction = $this->pay->transaction;
		$this->setTitle(array(
			Translator::trans('pay.byId', array('id' => $this->pay->id)),
			Translator::trans('pay.accept')
		));
		$this->setShortDescription(Translator::trans('pay.number',array('number' =>  $this->pay->id)));
		$this->setNavigation();

	}
	private function setNavigation(){
		$item = new MenuItem("transactions");
		$item->setTitle(Translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		Breadcrumb::addItem($item);

		$item = new MenuItem("transaction");
		$item->setTitle(Translator::trans('tranaction', array('id' => $this->transaction->id)));
		$item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
		$item->setIcon('clip-user');
		Breadcrumb::addItem($item);


		$item = new MenuItem("pay");
		$item->setTitle(Translator::trans('pay.accept'));
		$item->setURL(userpanel\url('transactions/pay/accept/'.$this->pay->id));
		$item->setIcon('fa fa-check');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
}
