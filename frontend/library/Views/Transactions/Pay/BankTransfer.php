<?php

namespace themes\clipone\Views\Transactions\Pay;

use packages\base\{Translator};
use packages\financial\Views\Transactions\Pay\BankTransfer as BankTransferView;
use packages\financial\{Transaction};
use packages\userpanel;
use packages\userpanel\Date;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class BankTransfer extends BankTransferView
{
    use FormTrait;
    use ViewTrait;

    /** @var Transaction */
    protected $transaction;

    public function __beforeLoad(): void
    {
        $this->transaction = $this->getTransaction();
        $this->setTitle([
            Translator::trans('pay.byBankTransfer'),
        ]);
        $this->setShortDescription(Translator::trans('transaction.number', ['number' => $this->transaction->id]));
        $this->setNavigation();
        $this->addBodyClass('transaction-pay-bankaccount');
        $this->addBodyClass('transaction-pay-banktransfer');
        $this->setFormData();
    }

    protected function getBankAccountsForSelect()
    {
        $options = [];
        foreach ($this->getBankAccounts() as $account) {
            $options[] = [
                'title' => $account->bank->title."[{$account->cart}]",
                'value' => $account->id,
            ];
        }

        return $options;
    }

    private function setNavigation()
    {
        $item = new MenuItem('transactions');
        $item->setTitle(Translator::trans('transactions'));
        $item->setURL(userpanel\url('transactions'));
        $item->setIcon('clip-users');
        Breadcrumb::addItem($item);

        $item = new MenuItem('transaction');
        $item->setTitle(Translator::trans('tranaction', ['id' => $this->transaction->id]));
        $item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
        $item->setIcon('clip-user');
        Breadcrumb::addItem($item);

        $item = new MenuItem('pay');
        $item->setTitle(Translator::trans('pay'));
        $item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
        $item->setIcon('fa fa-money');
        Breadcrumb::addItem($item);

        $item = new MenuItem('banktransfer');
        $item->setTitle(Translator::trans('pay.byBankTransfer'));
        $item->setURL(userpanel\url('transactions/pay/banktransfer/'.$this->transaction->id));
        $item->setIcon('clip-banknote');
        Breadcrumb::addItem($item);

        Navigation::active('transactions/list');
    }

    private function setFormData()
    {
        if (!$this->getDataForm('price')) {
            $this->setDataForm($this->transaction->remainPriceForAddPay(), 'price');
        }
        if (!$this->getDataForm('date')) {
            $this->setDataForm(Date::format('Y/m/d H:i:s'), 'date');
        }
    }
}
