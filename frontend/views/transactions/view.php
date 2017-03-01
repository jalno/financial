<?php
namespace themes\clipone\views\transactions;
use \packages\base\options;
use \packages\base\packages;
use \packages\base\translator;

use \packages\userpanel;
use \packages\userpanel\user;
use \packages\userpanel\date;

use \packages\financial\bankaccount;
use \packages\financial\transaction;
use \packages\financial\payport_pay;
use \packages\financial\transaction_pay;
use \packages\financial\views\transactions\view as transactionsView;

use \themes\clipone\viewTrait;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\views\listTrait;
use \themes\clipone\navigation\menuItem;

class view extends transactionsView{
	use viewTrait,listTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->transaction = $this->getData('transaction');
		$this->pays = $this->transaction->pays;
		$this->setTitle(array(
			translator::trans('title.transaction.view')
		));
		$this->setShortDescription(translator::trans('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->SetNoteBox();
		$this->setPays();

	}
	private function setNavigation(){
		$item = new menuItem("transactions");
		$item->setTitle(translator::trans('transactions'));
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-users');
		breadcrumb::addItem($item);

		$item = new menuItem("transaction");
		$item->setTitle(translator::trans('title.transaction.view'));
		$item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
		$item->setIcon('fa fa-television');
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
	protected function setPays(){
		$needacceptbtn = false;
		foreach($this->pays as &$pay){
			if($pay->status == transaction_pay::pending){
				$needacceptbtn = true;
			}
			$pay->date = date::format("Y/m/d H:i:s", $pay->date);
			$pay->price = translator::trans('currency.rial', array('number' => $pay->price));
			if($pay->method == transaction_pay::credit){
				$pay->method = translator::trans('pay.method.credit');
			}elseif($pay->method == transaction_pay::banktransfer){
				if($bankaccount = bankaccount::byId($pay->param('bankaccount'))){
					$pay->method = translator::trans('pay.byBankTransfer.withbank', array('bankaccount' => $bankaccount->title));
				}else{
					$pay->method = translator::trans('pay.byBankTransfer');
				}
				$pay->description = translator::trans('pay.byBankTransfer.withfollowup', array('followup' => $pay->param('followup')));
			}elseif($pay->method == transaction_pay::onlinepay){
				if($payport_pay = payport_pay::byId($pay->param('payport_pay'))){
					$pay->method = translator::trans('pay.byPayOnline.withpayport', array('payport' => $payport_pay->payport->title));
				}else{
					$pay->method = translator::trans('pay.byPayOnline');
				}
			}elseif($pay->method == transaction_pay::payaccepted){
				$acceptor = user::byId($pay->param('acceptor'));
				$pay->method = translator::trans('pay.method.payaccepted', array('acceptor' => $acceptor->getFullName()));
			}
		}
		if($needacceptbtn){
			$this->setButton('pay_accept',($this->canPayAccept and $this->transaction->status == transaction::unpaid), array(
				'title' => translator::trans('pay.accept'),
				'icon' => 'fa fa-check',
				'classes' => array('btn', 'btn-xs', 'btn-green')
			));
			$this->setButton('pay_reject', ($this->canPayReject and $this->transaction->status == transaction::unpaid), array(
				'title' => translator::trans('pay.reject'),
				'icon' => 'fa fa-times',
				'classes' => array('btn', 'btn-xs', 'btn-danger')
			));
		}
	}
	protected function paysHasDiscription(){
		foreach($this->pays as $pay){
			if($pay->description){
				return true;
			}
		}
		return false;
	}
	protected function paysHasStatus(){
		foreach($this->pays as $pay){
			if($pay->status != transaction_pay::accepted){
				return true;
			}
		}
		return false;
	}
	protected function Discounts(){
		$discounts = 0;
		foreach($this->transaction->products as $product){
			$discounts += $product->discount;
		}
		return $discounts;
	}
	protected function getTransActionLogo(){
		if($logoPath = options::get('packages.financial.transactions_logo')){
			return packages::package("financial")->url($logoPath);
		}
		return null;
	}
}
