<?php
namespace themes\clipone\views\transactions;
use \packages\financial\views\transactions\view as transactionsView;
use \packages\userpanel;
use \packages\userpanel\date;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\views\listTrait;
use \packages\base\translator;

class view extends transactionsView{
	use listTrait;
	protected $transaction;
	protected $hasdesc;
	function __beforeLoad(){
		$this->transaction = $this->getData('transaction');
		$this->setTitle(array(
			translator::trans('title.transaction.view')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->SetNoteBox();

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
		navigation::active("transactions/list");
	}
	private function SetNoteBox(){
		$this->hasdesc = false;
		foreach($this->transaction->products as $product){
			if($product->param('description')){
				$this->hasdesc = true;
				break;
			}
		}
	}
}
