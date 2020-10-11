<?php
namespace themes\clipone\views\transactions\pay;
use \packages\base\{translator, json};
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\{views\transactions\pay\onlinepay as onlinepayView, payport, currency};
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

class onlinepay extends onlinepayView{
	use viewTrait,formTrait;
	protected $transaction;
	function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			translator::trans('pay.method.onlinepay')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-online");
		$this->setFormData();
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
	protected function getPayportsForSelect(){
		$options = array();
		$currency = $this->transaction->currency;
		$remainPriceForPay = $this->transaction->remainPriceForAddPay();
		foreach ($this->getPayports() as $payport) {
			$payportcurrency = $payport->getCompatilbeCurrency($currency);
			if (!$payportcurrency) {
				continue;
			}
			$option = array(
				'title' => $payport->title,
				'value' => $payport->id,
				"data" => [
					"price" => $currency->changeTo($remainPriceForPay, $payportcurrency),
					"title" => $payportcurrency->title,
					"currency" => $payportcurrency->id,
				],
			);
			$options[] = $option;
		}
		return $options;
	}
	private function setFormData() {
		if (!$this->getDataForm("price")) {
			$this->setDataForm($this->transaction->remainPriceForAddPay(), "price");
		}
		if (!$this->getDataForm("currency")) {
			$this->setDataForm($this->transaction->currency->id, "currency");
		}
	}
}
