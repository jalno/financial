<?php
namespace themes\clipone\views\transactions;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\bankaccount;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\financial\payport_pay;
use \packages\financial\views\transactions\edit as transactionsEdit;
use \themes\clipone\navigation;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \packages\base\translator;
class edit extends transactionsEdit{
	use viewTrait, formTrait, listTrait;
	protected $transaction;
	protected $pays;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('edit'),
			$this->getTransactionData()->id
		));
		$this->setShortDescription(translator::trans('transaction.edit'));
		$this->setPays();
		$this->setNavigation();
		$this->setButtons();
		$this->setForm();
		$this->addBodyClass('transaction-edit');
	}
	private function setNavigation(){
		navigation::active("transactions/list");
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
			switch($pay->method){
				case(transaction_pay::credit):
					$pay->method = translator::trans('pay.method.credit');
					break;
				case(transaction_pay::banktransfer):
					if($bankaccount = bankaccount::byId($pay->param('bankaccount'))){
					$pay->method = translator::trans('pay.byBankTransfer.withbank', array('bankaccount' => $bankaccount->title));
					}else{
						$pay->method = translator::trans('pay.byBankTransfer');
					}
					$pay->description = translator::trans('pay.byBankTransfer.withfollowup', array('followup' => $pay->param('followup')));
					break;
				case(transaction_pay::onlinepay):
					if($payport_pay = payport_pay::byId($pay->param('payport_pay'))){
						$pay->method = translator::trans('pay.byPayOnline.withpayport', array('payport' => $payport_pay->payport->title));
					}else{
						$pay->method = translator::trans('pay.byPayOnline');
					}
					break;
				case(transaction_pay::payaccepted):
					$acceptor = userpanel\user::byId($pay->param('acceptor'));
					$pay->method = translator::trans('pay.method.payaccepted', array('acceptor' => $acceptor->getFullName()));
					break;
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
				'classes' => array('btn', 'btn-xs', 'btn-tael')
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
	public function setButtons(){
		$this->setButton('productEdit', $this->canEditProduct, array(
			'title' => translator::trans('financial.edit'),
			'icon' => 'fa fa-edit ',
			'classes' => array('btn', 'btn-xs', 'btn-teal', 'product-edit'),
			'data' => [
				'toggle' => 'modal'
			]
		));
		$this->setButton('productDelete', $this->canDeleteProduct, array(
			'title' => translator::trans('financial.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky', 'product-delete')
		));
		$this->setButton('pay_delete', $this->canPaydelete, array(
			'title' => translator::trans('financial.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky')
		));
	}
	private function setForm(){
		if($user = $this->getDataForm('user')){
			if($user = userpanel\user::byId($user)){
				$this->setDataForm($user->getFullName(), 'user_name');
			}
		}
	}
}
