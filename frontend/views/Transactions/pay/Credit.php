<?php
namespace themes\clipone\Views\Transactions\Pay;

use packages\base\{Translator};
use packages\userpanel;
use packages\userpanel\Date;
use themes\clipone\{Breadcrumb, Views\FormTrait, Navigation\MenuItem, Navigation, ViewTrait};
use packages\financial\Views\Transactions\Pay\Credit as CreditView;

class Credit extends CreditView {
	use ViewTrait, FormTrait;
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

		$item = new MenuItem("credit");
		$item->setTitle(Translator::trans('pay.byCredit'));
		$item->setURL(userpanel\url('transactions/pay/credit/'.$this->transaction->id));
		$item->setIcon('clip-phone-3');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
}
