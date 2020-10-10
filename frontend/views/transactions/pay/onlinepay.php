<?php
namespace themes\clipone\views\transactions\pay;

use packages\base\{Json, Translator};
use packages\financial\{Currency, Payport, Transaction, Currency\UnChangableException};
use packages\financial\views\transactions\pay\Onlinepay as OnlinepayView;
use packages\userpanel;
use packages\userpanel\{Date};
use themes\clipone\{Breadcrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};

class OnlinePay extends OnlinepayView {
	use ViewTrait, FormTrait;

	/** @var Transaction */
	protected $transaction;

	public function __beforeLoad(): void {
		$this->transaction = $this->getTransaction();
		$this->setTitle(array(
			translator::trans('pay.method.onlinepay')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-online");
	}
	protected function setNavigation(){
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
	protected function getPayportsForSelect(): array {
		$options = array();
		$currency = $this->transaction->currency;
		$payablePrice = $this->transaction->payablePrice();
		foreach ($this->getPayports() as $payport) {
			$currenciesID = array_column($payport->getCurrencies(), 'currency');
			if (!$currenciesID) {
				continue;
			}
			$validCurrencies = [];
			$key = array_search($currency->id, $currenciesID);
			if ($key !== false) {
				$validCurrencies[] = array(
					'price' => $payablePrice,
					'currency' => $currency,
				);
				unset($currenciesID[$key]);
				$currenciesID = array_values($currenciesID);
			}
			if ($currenciesID) {
				$currencies = (new Currency())->where('id', $currenciesID, 'IN')->get();
				foreach ($currencies as $payportCurrency) {
					try {
						$validCurrencies[] = array(
							'price' => $currency->changeTo($payablePrice, $payportCurrency),
							'currency' => $payportCurrency,
						);
					} catch (UnChangableException $e) {}
				}
			}
			$l = count($validCurrencies);
			foreach ($validCurrencies as $option) {
				$options[] = array(
					'title' => $payport->title . ($l > 1 ? ' (' . $option['currency']->title . ')' : ''),
					'value' => $payport->id,
					'data' => array(
						'price' => $option['price'],
						'title' => $option['currency']->title,
						'currency' => $option['currency']->id,
					),
				);
			}
		}
		return $options;
	}
}
