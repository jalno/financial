<?php
namespace themes\clipone\Views\Transactions;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\financial\Views\Transactions\Add as TransactionsAdd;
use \themes\clipone\ViewTrait;
use \themes\clipone\Views\FormTrait;
use \themes\clipone\Breadcrumb;
use \themes\clipone\Navigation;
use \themes\clipone\Navigation\MenuItem;
class Add extends TransactionsAdd{
	use ViewTrait,FormTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle([
			Translator::trans('tranaction'),
			Translator::trans('add')
		]);
		$this->setShortDescription(Translator::trans('transaction.add'));
		$this->setNavigation();
		$this->FormDate();
		$this->addBodyClass('transaction-add');
	}
	private function FormDate(){
		if(!$this->getDataForm('create_at')){
			$this->setDataForm(userpanel\Date::format('Y/m/d H:i:s', userpanel\Date::time()), 'create_at');
		}
		if(!$this->getDataForm('expire_at')){
			$this->setDataForm(userpanel\Date::format('Y/m/d H:i:s', (userpanel\Date::time() + 86400)), 'expire_at');
		}
	}
	private function setNavigation(){
		$item = new MenuItem("transactions");
		$item->setTitle(Translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		Breadcrumb::addItem($item);

		$item = new MenuItem("transaction");
		$item->setTitle(Translator::trans('transaction.add'));
		$item->setURL(userpanel\url('transactions/add'));
		$item->setIcon('fa fa-plus');
		Breadcrumb::addItem($item);
		Navigation::active("transactions/list");
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
