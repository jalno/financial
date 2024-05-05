<?php

namespace themes\clipone\Views\Transactions\Pay;

use packages\base\Translator;
use packages\financial\Views\Transactions\Pay\Accept as AcceptView;
use packages\userpanel;
use themes\clipone\Breadcrumb;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Accept extends AcceptView
{
    use ViewTrait;
    use FormTrait;
    protected $pay;
    protected $transaction;
    protected $action = 'accept';

    public function __beforeLoad()
    {
        $this->pay = $this->getPay();
        $this->transaction = $this->pay->transaction;
        $this->setTitle([
            Translator::trans('pay.byId', ['id' => $this->pay->id]),
            Translator::trans('pay.accept'),
        ]);
        $this->setShortDescription(Translator::trans('pay.number', ['number' => $this->pay->id]));
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
        $item->setTitle(Translator::trans('tranaction', ['id' => $this->transaction->id]));
        $item->setURL(userpanel\url('transactions/view/'.$this->transaction->id));
        $item->setIcon('clip-user');
        Breadcrumb::addItem($item);

        $item = new MenuItem('pay');
        $item->setTitle(Translator::trans('pay.accept'));
        $item->setURL(userpanel\url('transactions/pay/accept/'.$this->pay->id));
        $item->setIcon('fa fa-check');
        Breadcrumb::addItem($item);

        Navigation::active('transactions/list');
    }
}
