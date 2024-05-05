<?php
namespace themes\clipone\Views\Transactions;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\userpanel\Date;
use \packages\financial\Views\Transactions\Pay as PayView;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;
use \themes\clipone\ViewTrait;

class Pay extends PayView{
	use ViewTrait;
	protected $transaction;
	protected $methods = array();
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->methods = $this->getMethods();
		$this->setTitle(array(
			Translator::trans('title.transaction.view')
		));
		$this->setShortDescription(Translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay");
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
		$item->setIcon('fa fa-television');
		Breadcrumb::addItem($item);

		$item = new MenuItem("pay");
		$item->setTitle(Translator::trans('pay'));
		$item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
	protected function getColumnWidth(){
		return ($this->canViewGuestLink ? 12 : 6)/count($this->methods);
	}
}
