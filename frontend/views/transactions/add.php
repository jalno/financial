<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\base\frontend\theme;

use \packages\userpanel;

use \packages\financial\views\transactions\add as transactionsAdd;
use \packages\financial\transaction;
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
		$this->setTitle(array(
			translator::trans('tranaction'),
			translator::trans('add')
		));
		$this->setShortDescription(translator::trans('transaction.add'));
		$this->addAssets();
		$this->setNavigation();
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.add.js'));
		$this->addCSSFile(theme::url('assets/css/transaction.add.css'));
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
	protected function getProductsForSelect(){
		$products = array();
		foreach($this->getProducts() as $product){
			$products[] = array(
				'title' => $product->getTitle(),
				'value' => $product->getName()
			);
		}
		return $products;
	}
}
