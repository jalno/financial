<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\transactions\add as transactionsAdd;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
class add extends transactionsAdd{
	use viewTrait,formTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle([
			translator::trans('tranaction'),
			translator::trans('add')
		]);
		$this->setShortDescription(translator::trans('transaction.add'));
		$this->setNavigation();
		$this->FormDate();
		$this->addBodyClass('transaction-add');
	}
	private function FormDate(){
		if(!$this->getDataForm('create_at')){
			$this->setDataForm(userpanel\date::format('Y/m/d H:i:s', userpanel\date::time()), 'create_at');
		}
		if(!$this->getDataForm('expire_at')){
			$this->setDataForm(userpanel\date::format('Y/m/d H:i:s', (userpanel\date::time() + 86400)), 'expire_at');
		}
	}
	private function setNavigation(){
		$item = new menuItem("transactions");
		$item->setTitle(translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction");
		$item->setTitle(translator::trans('transaction.add'));
		$item->setURL(userpanel\url('transactions/add'));
		$item->setIcon('fa fa-plus');
		breadcrumb::addItem($item);
		navigation::active("transactions/list");
	}
	protected function getCurrenciesForSelect():array{
		$currencies = [];
		foreach($this->getCurrencies() as $currency){
			$currencies[] = [
				'title' => $currency->title,
				'value' => $currency->id,
				'data' => [
					'title' => $currency->title
				]
			];
		}
		return $currencies;
	}
}
