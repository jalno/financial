<?php
namespace themes\clipone\views\transactions;
use packages\base\{options, packages};
use packages\userpanel;
use packages\userpanel\{user, date};
use packages\financial\{Authorization, Bank\Account, PaymentMethodManager, transaction, payport_pay, transaction_pay, views\transactions\view as transactionsView};
use packages\financial\Contracts\IPaymentMethod;
use themes\clipone\{viewTrait, views\listTrait, views\formTrait, breadcrumb, navigation, navigation\menuItem, views\TransactionTrait};

class view extends transactionsView
{
	use viewTrait, listTrait, formTrait, TransactionTrait;

	/**
	 * @var IPaymentMethod[]
	 */
	public array $paymentMethods = [];

	protected $transaction;
	protected $pays = [];
	protected $hasdesc = false;
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
			Authorization::is_accessed('transactions_reimburse') and
			$this->transactionHasPaysToReimburse($this->transaction)
		);
	}

	public function getPayMethodForShow(Transaction_pay $pay): string
	{
		if (transaction_pay::PAYACCEPTED == $pay->method) {
			$acceptor = $pay->param('acceptor') ?: '-';
			$user = is_numeric($acceptor) ? User::byId($pay->param('acceptor')) : $acceptor;
			return t('pay.method.payaccepted', ['acceptor' => $user instanceof User ? $user->getFullName() : $acceptor]);
		}

		if (!isset($this->paymentMethods[$pay->method])) {
			return $pay->method;
		}

		return $this->paymentMethods[$pay->method]->getPayTitle($pay);
	}

	public function getPaysStatusIcon(): string
	{
		if (Transaction::UNPAID == $this->transaction->status) {
			$query = (new Transaction_pay())->where('transaction', $this->transaction->id);
			$query->where('status', [Transaction_pay::PENDING, Transaction_pay::REJECTED], 'in');
			$query->orderBy('status', 'DESC');
			$pays = $query->get(null, ['id', 'updated_at', 'status']);
			foreach ($pays as $pay) {
				if (Transaction_pay::PENDING == $pay->status) {
					return '<i class="fa fa-spin fa-spinner text-warning tooltips transaction-pays-status-icon hidden-print" title="'.t('titles.financial.progressing-pending-pays').'"></i>';
				} elseif ($pay->updated_at > Date::time() - 86400) {
					return '<i class="fa fa-info-circle text-danger tooltips transaction-pays-status-icon hidden-print" title="'.t('error.financial.rejected-pays').'"></i>';
				}
			}
		}

		return '';
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
	protected function transactionHasPaysToReimburse(Transaction $transaction): bool {
		$methods = array_keys($this->paymentMethods);
		return $methods and (new Transaction_Pay)
				->where('transaction', $transaction->id)
				->where('method',$methods, 'in')
				->where('status', Transaction_Pay::ACCEPTED)
				->has();
	}
}
