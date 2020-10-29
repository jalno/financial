<?php
namespace themes\clipone\views\transactions\pay;

use packages\base\{Translator};
use packages\userpanel;
use packages\userpanel\Date;
use themes\clipone\{Breadcrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};
use packages\financial\views\transactions\pay\credit as CreditView;

class credit extends CreditView {
	use viewTrait, formTrait;
	protected $transaction;
	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			t('pay.byCredit')
		));
		$this->setShortDescription(t('transaction.number', array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass('pay');
		$this->addBodyClass('pay-by-credit');
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
