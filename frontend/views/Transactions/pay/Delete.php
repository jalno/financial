<?php
namespace themes\clipone\Views\Transactions\Pay;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\financial\Views\Transactions\Pay\Delete as PayDelete;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;

class Delete extends PayDelete{
	use ViewTrait, FormTrait;
	protected $pay;
	function __beforeLoad(){
		$this->pay = $this->getPayData();
		$this->setTitle(array(
			Translator::trans('transaction.pay.delete'),
			$this->pay->id
		));
		$this->setShortDescription(Translator::trans('transaction.pay.delete'));
		$this->setNavigation();
	}

	private function setNavigation(){
		$item = new MenuItem("transactions");
		$item->setTitle(Translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		Breadcrumb::addItem($item);

		$item = new MenuItem("transaction");
		$item->setTitle(Translator::trans('transaction.edit'));
		$item->setURL(userpanel\url('transactions/edit/'.$this->pay->transaction->id));
		$item->setIcon('fa fa-edit');
		Breadcrumb::addItem($item);

		$item = new MenuItem("transaction.pay");
		$item->setTitle(Translator::trans('transaction.pay.delete'));
		$item->setIcon('fa fa-trash');
		Breadcrumb::addItem($item);
		Navigation::active("transactions/list");
	}
}
