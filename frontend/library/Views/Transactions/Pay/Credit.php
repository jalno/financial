<?php

namespace themes\clipone\Views\Transactions\Pay;

use packages\base\{Translator};
use packages\financial\Views\Transactions\Pay\Credit as CreditView;
use packages\userpanel;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Credit extends CreditView
{
    use ViewTrait;
    use FormTrait;
    protected $transaction;

    public function __beforeLoad(): void
    {
        $this->transaction = $this->getTransaction();
        $this->setTitle([
            t('pay.byCredit'),
        ]);
        $this->setShortDescription(t('transaction.number', ['number' => $this->transaction->id]));
        $this->setNavigation();
        $this->addBodyClass('pay');
        $this->addBodyClass('pay-by-credit');
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

        $item = new MenuItem('credit');
        $item->setTitle(Translator::trans('pay.byCredit'));
        $item->setURL(userpanel\url('transactions/pay/credit/'.$this->transaction->id));
        $item->setIcon('clip-phone-3');
        Breadcrumb::addItem($item);

        Navigation::active('transactions/list');
    }
}
