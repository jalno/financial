<?php
namespace themes\clipone\Views\Transactions;
use packages\base\{Options, Packages};
use packages\userpanel;
use packages\userpanel\{User, Date};
use packages\financial\{Authorization, Bank\Account, Transaction, PayPortPay, TransactionPay, Views\Transactions\View as TransactionsView};
use themes\clipone\{ViewTrait, Views\ListTrait, Views\FormTrait, Breadcrumb, Navigation, Navigation\MenuItem, Views\TransactionTrait};

class View extends TransactionsView {
	use ViewTrait, ListTrait, FormTrait, TransactionTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	protected $discounts = 0;
	protected $vats = 0;
	public function __beforeLoad(){
		$this->transaction = $this->getTransaction();
		$this->pays = $this->transaction->pays;
		$this->setTitle(t("title.transaction.view"));
		$this->setShortDescription(t("transaction.number",array("number" =>  $this->transaction->id)));
		$this->setNavigation();
		$this->SetNoteBox();
		$this->setPays();
		$this->addBodyClass("transaction-view");

		$this->canReimburse = (
			Authorization::is_accessed("transactions_reimburse") and
			$this->transactionHasPaysToReimburse($this->transaction)
		);
	}
	private function setNavigation(){
		Navigation::active("transactions/list");
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
			if($pay->status == TransactionPay::pending){
				$needacceptbtn = true;
			}
			$pay->date = Date::format("Y/m/d H:i:s", $pay->date);
			$pay->price = $this->numberFormat(abs($pay->price)) . " " . $pay->currency->title;
			if($pay->method == TransactionPay::credit){
				$pay->method = t("pay.method.credit");
			}elseif($pay->method == TransactionPay::banktransfer){
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
				
			}elseif($pay->method == TransactionPay::onlinepay){
				if($payport_pay = PayPortPay::byId($pay->param("payport_pay"))){
					$pay->method = t("pay.byPayOnline.withpayport", array("payport" => $payport_pay->payport->title));
				}else{
					$pay->method = t("pay.byPayOnline");
				}
			}elseif($pay->method == TransactionPay::payaccepted){
				$acceptor = User::byId($pay->param("acceptor"));
				$pay->method = t("pay.method.payaccepted", array("acceptor" => $acceptor->getFullName()));
			}
		}
		if($needacceptbtn){
			$this->setButton("pay_accept", $this->canPayAccept, array(
				"title" => t("pay.accept"),
				"icon" => "fa fa-check",
				"classes" => array("btn", "btn-xs", "btn-green")
			));
			$this->setButton("pay_reject", $this->canPayReject, array(
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
			if($pay->status != TransactionPay::accepted){
				return true;
			}
		}
		return false;
	}
	protected function getTransActionLogo(){
		if($logoPath = Options::get("packages.financial.transactions_logo")){
			return Packages::package("financial")->url($logoPath);
		}
		return null;
	}
	protected function transactionHasPaysToReimburse(Transaction $transaction): bool {
		return boolval(
			(new TransactionPay)
				->where("transaction", $transaction->id)
				->where("method",
						array(
							TransactionPay::CREDIT,
							TransactionPay::ONLINEPAY,
							TransactionPay::BANKTRANSFER,
						),
						"IN"
				)
				->where("status", TransactionPay::ACCEPTED)
		->has());
	}
}
