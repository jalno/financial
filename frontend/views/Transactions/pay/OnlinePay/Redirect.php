<?php
namespace themes\clipone\Views\Transactions\Pay\OnlinePay;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\userpanel\Date;
use \packages\financial\Views\Transactions\Pay\OnlinePay\Redirect as RedirectView;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
class Redirect extends RedirectView{
	use ViewTrait,FormTrait;
	protected $transaction;
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->setTitle(Translator::trans('pay.redirect'));
		$this->setShortDescription(Translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-online");
		$this->addBodyClass("transaction-pay-redirect");
	}
	protected function createFormData(){
		foreach($this->getRedirect()->data as $key => $value){
			$this->createField(array(
				'type' => 'hidden',
				'name' => $key,
				'value' => $value
			));
		}
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
		$item->setTitle(Translator::trans('pay'));
		$item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		Breadcrumb::addItem($item);

		$item = new MenuItem("onlinepay");
		$item->setTitle(Translator::trans('pay.method.onlinepay'));
		$item->setURL(userpanel\url('transactions/pay/onlinepay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
}
