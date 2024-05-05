<?php
namespace themes\clipone\views\Transactions\Pay;

use packages\base\{Json, Translator};
use packages\financial\{Currency, PayPort, Transaction, Currency\UnChangableException};
use packages\financial\Views\Transactions\Pay\OnlinePay as OnlinePayView;
use packages\userpanel;
use packages\userpanel\{Date};
use themes\clipone\{Breadcrumb, Views\FormTrait, Navigation\MenuItem, Navigation, ViewTrait};

class OnlinePay extends OnlinePayView {
	use ViewTrait, FormTrait;

	/** @var Transaction */
	protected $transaction;

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			Translator::trans('pay.method.onlinepay')
		));
		$this->setShortDescription(Translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-online");
		$this->setFormData();
	}
	protected function setNavigation(){
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

		$item = new menuItem("pay");
		$item->setTitle(Translator::trans('pay'));
		$item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		Breadcrumb::addItem($item);

		$item = new MenuItem("banktransfer");
		$item->setTitle(Translator::trans('pay.byBankTransfer'));
		$item->setURL(userpanel\url('transactions/pay/banktransfer/'.$this->transaction->id));
		$item->setIcon('clip-banknote');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
	protected function getPayportsForSelect(): array {
		$options = array();
		$currency = $this->transaction->currency;
		$remainPriceForPay = $this->transaction->remainPriceForAddPay();
		foreach ($this->getPayports() as $payport) {
			$payportcurrency = $payport->getCompatilbeCurrency($currency);
			if (!$payportcurrency) {
				continue;
			}
			$options[] = array(
				'title' => $payport->title,
				'value' => $payport->id,
				"data" => [
					"price" => $currency->changeTo($remainPriceForPay, $payportcurrency),
					"title" => $payportcurrency->title,
					"currency" => $payportcurrency->id,
				],
			);
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
