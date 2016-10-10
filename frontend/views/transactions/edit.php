<?php
namespace themes\clipone\views\transactions;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\bankaccount;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\financial\payport_pay;
use \packages\financial\views\transactions\edit as transactionsEdit;
use \themes\clipone\breadcrumb;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \packages\base\translator;
use \packages\base\frontend\theme;

class edit extends transactionsEdit{
	use viewTrait,formTrait,listTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('edit'),
			$this->getTransactionData()->id
		));
		$this->setShortDescription(translator::trans('transaction.edit'));
		$this->addAssets();
		$this->setPays();
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.edit.js'));
	}
	protected function setPays(){
		$needacceptbtn = false;
		$this->pays = $this->getTransactionData()->pays;
		foreach($this->pays as $pay){
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
		foreach($this->getTransactionData()->pays as $pay){
			if($pay->description){
				return true;
			}
		}
		return false;
	}
	protected function paysHasStatus(){
		foreach($this->getTransactionData()->pays as $pay){
			if($pay->status != transaction_pay::accepted){
				return true;
			}
		}
		return false;
	}
}
