<?php
namespace themes\clipone\views\financial\PaymentMethods;

use packages\financial\Transaction;
use packages\userpanel\views\Form;
use themes\clipone\{Breadcrumb, views\FormTrait, navigation\MenuItem, Navigation, ViewTrait};
use function packages\userpanel\url;

class OnlinePayView extends Form
{
    use ViewTrait, FormTrait;

    public ?Transaction $transaction = null;
    /**
     * @var Payport[]
     */
    public array $payports = [];
    protected $file = 'html/PaymentMethods/OnlinePaymentMethod.php';

    public function __beforeLoad(): void
    {
        $this->setTitle(t('pay.method.onlinepay'));
        $this->setShortDescription(t('transaction.number',array('number' =>  $this->transaction->id)));
        $this->setNavigation();
        $this->addBodyClass("transaction-pay-online");
		$this->addBodyClass("transaction-payment-method");
        $this->setFormData();
    }

    public function setNavigation()
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

    protected function getPayportsForSelect(): array
    {
        $options = [];
        $currency = $this->transaction->currency;
        $remainPriceForPay = $this->transaction->remainPriceForAddPay();
        foreach ($this->payports as $payport) {
            $payportcurrency = $payport->getCompatilbeCurrency($currency);
            if (!$payportcurrency) {
                continue;
            }
            $options[] = array(
                'title' => $payport->title,
                'value' => $payport->id,
                "data" => [
                    "price" => $currency->changeTo($remainPriceForPay, $payportcurrency),
                    "title" => $payportcurrency->title,
                    "currency" => $payportcurrency->id,
                ],
            );
        }
        return $options;
    }

    private function setFormData()
    {
        if (!$this->getDataForm("price")) {
            $this->setDataForm($this->transaction->remainPriceForAddPay(), "price");
        }
        if (!$this->getDataForm("currency")) {
            $this->setDataForm($this->transaction->currency->id, "currency");
        }
    }
}
