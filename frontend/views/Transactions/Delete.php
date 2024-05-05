<?php

namespace themes\clipone\Views\Transactions;

use packages\base\Translator;
use packages\financial\Views\Transactions\Delete as TransactionsDelete;
use packages\userpanel;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Delete extends TransactionsDelete
{
    use ViewTrait;
    use ListTrait;
    protected $transaction;
    protected $pays;
    protected $hasdesc;

    public function __beforeLoad()
    {
        $this->setTitle([
            Translator::trans('delete'),
            $this->getTransactionData()->id,
        ]);
        $this->setShortDescription(Translator::trans('transaction.delete'));
        $this->setNavigation();
    }

    private function setNavigation()
    {
        $item = new MenuItem('transactions');
        $item->setTitle(Translator::trans('transactions'));
        $item->setURL(userpanel\url('transactions'));
        $item->setIcon('clip-users');
        Breadcrumb::addItem($item);

        $item = new MenuItem('transaction');
        $item->setTitle(Translator::trans('transaction.delete'));
        $item->setURL(userpanel\url('transactions/delete/'.$this->getTransactionData()->id));
        $item->setIcon('fa fa-trash');
        Breadcrumb::addItem($item);
        Navigation::active('transactions/list');
    }
}
