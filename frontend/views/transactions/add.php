<?php
namespace themes\clipone\views\transactions;
use \packages\base\translator;
use \packages\base\frontend\theme;

use \packages\financial\views\transactions\add as transactionsAdd;
use \themes\clipone\viewTrait;
use \themes\clipone\views\formTrait;

use \packages\financial\transaction;

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
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.add.js'));
		$this->addCSSFile(theme::url('assets/css/transaction.add.css'));
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
