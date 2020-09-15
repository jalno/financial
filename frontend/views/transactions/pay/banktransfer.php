<?php
namespace themes\clipone\views\transactions\pay;

use packages\base\{Translator};
use packages\financial\{Transaction};
use packages\userpanel;
use packages\userpanel\date;
use themes\clipone\{BreadCrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};
use packages\financial\views\transactions\pay\Banktransfer as BanktransferView;

class Banktransfer extends BanktransferView {
	use FormTrait, ViewTrait;

	/** @var Transaction */
	protected $transaction;

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			translator::trans('pay.byBankTransfer')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-bankaccount");
		$this->addBodyClass("transaction-pay-banktransfer");
		$this->setDataForm($this->transaction->remainPriceForAddPay(), "price");
		$this->setDataForm(Date::format("Y/m/d H:i:s"), "date");
	}
	protected function getBankAccountsForSelect(){
		$options = array();
		foreach ($this->getBankAccounts() as $account){
			$options[] = array(
				"title" => $account->bank->title . "[{$account->cart}]",
				"value" => $account->id
			);
		}
		return $options;
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

		$item = new menuItem("banktransfer");
		$item->setTitle(translator::trans('pay.byBankTransfer'));
		$item->setURL(userpanel\url('transactions/pay/banktransfer/'.$this->transaction->id));
		$item->setIcon('clip-banknote');
		breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}
}
