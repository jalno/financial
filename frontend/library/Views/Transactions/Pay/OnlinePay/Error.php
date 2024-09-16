<?php

namespace themes\clipone\Views\Transactions\Pay\OnlinePay;

use packages\base\Translator;
use packages\financial\Views\Transactions\Pay\OnlinePay\Error as ErrorView;
use packages\userpanel;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Error extends ErrorView
{
    use ViewTrait;
    use FormTrait;
    protected $pay;
    protected $transaction;

    public function __beforeLoad()
    {
        $this->pay = $this->getPay();
        $this->transaction = $this->pay->transaction;
        $this->setTitle([
            t('pay.method.onlinepay'),
        ]);
        $this->setShortDescription(t('transaction.number', ['number' => $this->transaction->id]));
        $this->setNavigation();
        $this->addBodyClass('transaction-pay-online');
        $this->addBodyClass('transaction-pay-callback');
    }

    private function setNavigation()
    {
        $item = new MenuItem('transactions');
        $item->setTitle(t('transactions'));
        $item->setURL(userpanel\url('transactions'));
        $item->setIcon('clip-users');
        Breadcrumb::addItem($item);

        $item = new MenuItem('transaction');
        $item->setTitle(t('tranaction', ['id' => $this->transaction->id]));
        $item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
        $item->setIcon('clip-user');
        Breadcrumb::addItem($item);

        $item = new MenuItem('pay');
        $item->setTitle(t('pay'));
        $item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
        $item->setIcon('fa fa-money');
        Breadcrumb::addItem($item);

        $item = new MenuItem('banktransfer');
        $item->setTitle(t('pay.byBankTransfer'));
        $item->setURL(userpanel\url('transactions/pay/banktransfer/'.$this->transaction->id));
        $item->setIcon('clip-banknote');
        Breadcrumb::addItem($item);

        Navigation::active('transactions/list');
    }
}
