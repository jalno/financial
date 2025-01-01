<?php
namespace themes\clipone\views\financial\PaymentMethods;

use packages\financial\Transaction;
use packages\userpanel\views\Form;
use themes\clipone\{Breadcrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};
use function packages\userpanel\url;

class CreditView extends Form
{
	use ViewTrait, FormTrait;

	public ?Transaction $transaction = null;
	public float $price = 0;
	protected $file = 'html/PaymentMethods/CreditPaymentMethod.php';

	public function __beforeLoad(): void
	{
		$this->setTitle(array(
			t('pay.byCredit')
		));
		$this->setShortDescription(t('transaction.number', array('number' =>  $this->transaction->id)));
		$this->setNavigation();
		$this->addBodyClass('pay');
		$this->addBodyClass('pay-by-credit');

		$this->initFormData();
	}

	private function setNavigation(){
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

		$item = new MenuItem("credit");
		$item->setTitle(t('pay.byCredit'));
		$item->setURL(url('transactions/pay/credit/'.$this->transaction->id));
		$item->setIcon('clip-phone-3');
		Breadcrumb::addItem($item);

		navigation::active("transactions/list");
	}

	private function initFormData()
	{
		if (!$this->getDataForm('price')) {
			$this->setDataForm($this->price, 'price');
		}
	}
}
