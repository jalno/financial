<?php

namespace themes\clipone\Views\Transactions;

use packages\base\Translator;
use packages\financial\Views\Transactions\Pay as PayView;
use packages\userpanel;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\ViewTrait;

class Pay extends PayView
{
    use ViewTrait;
    protected $transaction;
    protected $methods = [];

    public function __beforeLoad()
    {
        $this->transaction = $this->getTransaction();
        $this->methods = $this->getMethods();
        $this->setTitle([
            t('title.transaction.view'),
        ]);
        $this->setShortDescription(t('transaction.number', ['number' => $this->transaction->id]));
        $this->setNavigation();
        $this->addBodyClass('transaction-pay');
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
        $item->setIcon('fa fa-television');
        Breadcrumb::addItem($item);

        $item = new MenuItem('pay');
        $item->setTitle(t('pay'));
        $item->setURL(userpanel\url('transactions/pay/'.$this->transaction->id));
        $item->setIcon('fa fa-money');
        Breadcrumb::addItem($item);

        Navigation::active('transactions/list');
    }

    protected function getColumnWidth()
    {
        return ($this->canViewGuestLink ? 12 : 6) / count($this->methods);
    }
}
