<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\views\transactions\pay\banktransfer as banktransferView;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

class banktransfer extends banktransferView{
	use viewTrait,formTrait;
	protected $transaction;
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			translator::trans('pay.byBankTransfer')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
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
	protected function getBankAccountsForSelect(){
		$options = array();
		foreach($this->getBankAccounts() as $account){
			$options[] = array(
				'title' => $account->title,
				'value' => $account->id
			);
		}
		return $options;
	}
}
