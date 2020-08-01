<?php
namespace themes\clipone\views\transactions;
use packages\base\{options, packages};
use packages\userpanel;
use packages\userpanel\{user, date};
use packages\financial\{Bank\Account, transaction, payport_pay, transaction_pay, views\transactions\view as transactionsView};
use themes\clipone\{viewTrait, views\listTrait, views\formTrait, breadcrumb, navigation, navigation\menuItem, views\TransactionTrait};

class view extends transactionsView {
	use viewTrait, listTrait, formTrait, TransactionTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	protected $discounts = 0;
	public function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->pays = $this->transaction->pays;
		$this->setTitle(t("title.transaction.view"));
		$this->setShortDescription(t("transaction.number",array("number" =>  $this->transaction->id)));
		$this->setNavigation();
		$this->SetNoteBox();
		$this->setPays();
		$this->addBodyClass("transaction-view");
	}
	private function setNavigation(){
		navigation::active("transactions/list");
	}
	private function SetNoteBox(){
		$this->hasdesc = false;
		foreach ($this->transaction->products as $product){
			if ($product->param("description")) {
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
			$pay->price = $this->numberFormat(abs($pay->price)) . " " . $pay->currency->title;
			if($pay->method == transaction_pay::credit){
				$pay->method = t("pay.method.credit");
			}elseif($pay->method == transaction_pay::banktransfer){
				if($bankaccount = Account::byId($pay->param("bankaccount"))){
					$pay->method = t("pay.byBankTransfer.withbank", array("bankaccount" => $bankaccount->bank->title . "[{$bankaccount->cart}]"));
				}else{
					$pay->method = t("pay.byBankTransfer");
				}
				$description = "";
				if ($pay->param("followup")) {
					$description = t("pay.byBankTransfer.withfollowup", array("followup" => $pay->param("followup")));
				}
				if ($pay->param("description")) {
					$description .= "\n<br>" . t("financial.transaction.banktransfer.description") . ": " . $pay->param("description");
				}
				$attachment = $pay->param("attachment");
				if ($attachment) {
					$url = Packages::package("financial")->url($attachment);
					$description .= "\n<br><a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-paperclip\"></i> " . t("pay.banktransfer.attachment") . "</a>";
				}
				$pay->description = $description;
				
			}elseif($pay->method == transaction_pay::onlinepay){
				if($payport_pay = payport_pay::byId($pay->param("payport_pay"))){
					$pay->method = t("pay.byPayOnline.withpayport", array("payport" => $payport_pay->payport->title));
				}else{
					$pay->method = t("pay.byPayOnline");
				}
			}elseif($pay->method == transaction_pay::payaccepted){
				$acceptor = user::byId($pay->param("acceptor"));
				$pay->method = t("pay.method.payaccepted", array("acceptor" => $acceptor->getFullName()));
			}
		}
		if($needacceptbtn){
			$this->setButton("pay_accept",($this->canPayAccept and $this->transaction->status == transaction::unpaid), array(
				"title" => t("pay.accept"),
				"icon" => "fa fa-check",
				"classes" => array("btn", "btn-xs", "btn-green")
			));
			$this->setButton("pay_reject", ($this->canPayReject and $this->transaction->status == transaction::unpaid), array(
				"title" => t("pay.reject"),
				"icon" => "fa fa-times",
				"classes" => array("btn", "btn-xs", "btn-danger")
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
	protected function getTransActionLogo(){
		if($logoPath = options::get("packages.financial.transactions_logo")){
			return packages::package("financial")->url($logoPath);
		}
		return null;
	}
}
