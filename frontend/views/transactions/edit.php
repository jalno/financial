<?php
namespace themes\clipone\views\transactions;
use packages\userpanel;
use packages\userpanel\date;
use themes\clipone\{navigation, viewTrait, views\listTrait, views\formTrait, views\TransactionTrait};
use packages\financial\{Bank\Account, transaction, transaction_pay, payport_pay, views\transactions\edit as transactionsEdit};

class edit extends transactionsEdit{
	use viewTrait, formTrait, listTrait, TransactionTrait;
	protected $transaction;
	protected $pays;
	function __beforeLoad(){
		$this->transaction = $this->getTransactionData();
		$this->setTitle(array(
			t('edit'),
			$this->transaction->id
		));
		$this->setShortDescription(t('transaction.edit'));
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
		$this->pays = $this->transaction->pays;
		foreach($this->pays as $pay){
			if($pay->status == transaction_pay::pending){
				$needacceptbtn = true;
			}
			switch($pay->method){
				case(transaction_pay::credit):
					$pay->method = t('pay.method.credit');
					break;
				case(transaction_pay::banktransfer):
					if ($bankaccount = Account::byId($pay->param('bankaccount'))) {
						$pay->method = t('pay.byBankTransfer.withbank', array('bankaccount' => $bankaccount->cart));
					} else {
						$pay->method = t('pay.byBankTransfer');
					}
					$pay->description = t('pay.byBankTransfer.withfollowup', array('followup' => $pay->param('followup')));
					break;
				case(transaction_pay::onlinepay):
					if($payport_pay = payport_pay::byId($pay->param('payport_pay'))){
						$pay->method = t('pay.byPayOnline.withpayport', array('payport' => $payport_pay->payport->title));
					}else{
						$pay->method = t('pay.byPayOnline');
					}
					break;
				case(transaction_pay::payaccepted):
					$acceptor = userpanel\user::byId($pay->param('acceptor'));
					$pay->method = t('pay.method.payaccepted', array('acceptor' => $acceptor->getFullName()));
					break;
			}
		}
		if($needacceptbtn){
			$this->setButton('pay_accept',($this->canPayAccept and $this->transaction->status == transaction::unpaid), array(
				'title' => t('pay.accept'),
				'icon' => 'fa fa-check',
				'classes' => array('btn', 'btn-xs', 'btn-green')
			));
			$this->setButton('pay_reject', ($this->canPayReject and $this->transaction->status == transaction::unpaid), array(
				'title' => t('pay.reject'),
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
			'title' => t('financial.edit'),
			'icon' => 'fa fa-edit ',
			'classes' => array('btn', 'btn-xs', 'btn-teal', 'product-edit'),
			'data' => [
				'toggle' => 'modal'
			]
		));
		$this->setButton('productDelete', $this->canDeleteProduct, array(
			'title' => t('financial.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky', 'product-delete')
		));
		$this->setButton('pay_delete', $this->canPaydelete, array(
			'title' => t('financial.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky')
		));
		$this->setButton('pay_edit', $this->canEditPays, array(
			'title' => t('financial.edit'),
			'icon' => 'fa fa-edit',
			'classes' => array('btn', 'btn-xs', 'btn-teal'),
			'data' => array(
				'action' => 'edit',
			),
		));
	}
	private function setForm(){
		if($user = $this->getDataForm('user')){
			if($user = userpanel\user::byId($user)){
				$this->setDataForm($user->getFullName(), 'user_name');
			}
		}
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
