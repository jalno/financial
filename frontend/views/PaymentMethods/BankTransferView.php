<?php
namespace themes\clipone\views\financial\PaymentMethods;

use packages\financial\Bank\Account;
use packages\financial\PaymentMethdos\BankTransferPaymentMethod;
use packages\userpanel\views\Form;
use packages\financial\Transaction;
use packages\financial\Transaction_pay as TransactionPay;
use packages\userpanel\date;
use themes\clipone\{BreadCrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};
use function packages\userpanel\url;

class BankTransferView extends Form
{
	use FormTrait, ViewTrait;

	public ?Transaction $transaction = null;
	/**
	 * @var Account[]
	 */
	public array $bankAccounts = [];
	protected $file = 'html/PaymentMethods/BankTransferPaymentMethod.php';
	/**
	 * @var array<int,Account>
	 */
	private array $accountsCache = [];

	public function __beforeLoad(): void {
		$this->setTitle(t('pay.byBankTransfer'));
		$this->setShortDescription(t('transaction.number',array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass("transaction-pay-bankaccount");
		$this->addBodyClass("transaction-pay-banktransfer");
		$this->setFormData();
	}

	public function getBankAccountsForSelect(): array
	{
		$options = array();
		foreach ($this->bankAccounts as $account){
			$options[] = [
				"title" => $account->bank->title . "[{$account->cart}]",
				"value" => $account->id
			];
		}
		return $options;
	}

	public function getAccount(TransactionPay $pay): ?Account
	{
		$bankaccountId = $pay->param("bankaccount");
		if (!$bankaccountId) {
			return null;
		}

		return $this->accountsCache[$bankaccountId] ?? $this->accountsCache[$bankaccountId] = (new Account())->byID($bankaccountId);
	}

	/**
	 * @return TransactionPay[]
	 */
	public function getExistsBanktransferPays(): array
	{
		return (new TransactionPay())
			->where("transaction", $this->transaction->id)
			->where("method", BankTransferPaymentMethod::getInstance()->getName())
			->get();
	}

	private function setNavigation(): void
	{
		$item = new MenuItem("transactions");
		$item->setTitle(t('transactions'));
		$item->setURL(url('transactions'));
		$item->setIcon('clip-users');
		Breadcrumb::addItem($item);

		$item = new MenuItem("transaction");
		$item->setTitle(t('tranaction', array('id' => $this->transaction->id)));
		$item->setURL(url('transactions/view/'.$this->transaction->id));
		$item->setIcon('clip-user');
		Breadcrumb::addItem($item);

		$item = new MenuItem("pay");
		$item->setTitle(t('pay'));
		$item->setURL(url('transactions/pay/'.$this->transaction->id));
		$item->setIcon('fa fa-money');
		Breadcrumb::addItem($item);

		$item = new MenuItem("banktransfer");
		$item->setTitle(t('pay.byBankTransfer'));
		$item->setURL(url('transactions/pay/banktransfer/'.$this->transaction->id));
		$item->setIcon('clip-banknote');
		Breadcrumb::addItem($item);

		Navigation::active("transactions/list");
	}
	private function setFormData(): void
	{
		if (!$this->getDataForm("price")) {
			$this->setDataForm($this->transaction->remainPriceForAddPay(), "price");
		}
		if (!$this->getDataForm("date")) {
			$this->setDataForm(Date::format("Y/m/d H:i:s"), "date");
		}
	}
}
