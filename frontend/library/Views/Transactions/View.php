<?php

namespace themes\clipone\Views\Transactions;

use packages\base\Options;
use packages\base\Packages;
use packages\financial\Authorization;
use packages\financial\Bank\Account;
use packages\financial\PayPortPay;
use packages\financial\Transaction;
use packages\financial\TransactionPay;
use packages\financial\Views\Transactions\View as TransactionsView;
use packages\userpanel\Date;
use packages\userpanel\User;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\Views\TransactionTrait;
use themes\clipone\ViewTrait;

class View extends TransactionsView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;
    use TransactionTrait;
    protected $transaction;
    protected $pays;
    protected $hasdesc;
    protected $discounts = 0;
    protected $vats = 0;

    public function __beforeLoad()
    {
        $this->transaction = $this->getTransaction();
        $this->pays = $this->transaction->pays;
        $this->setTitle(t('title.transaction.view'));
        $this->setShortDescription(t('transaction.number', ['number' => $this->transaction->id]));
        $this->setNavigation();
        $this->SetNoteBox();
        $this->setPays();
        $this->addBodyClass('transaction-view');

        $this->canReimburse = (
            Authorization::is_accessed('transactions_reimburse')
            and $this->transactionHasPaysToReimburse($this->transaction)
        );
    }

    private function setNavigation()
    {
        Navigation::active('transactions/list');
    }

    private function SetNoteBox()
    {
        $this->hasdesc = false;
        foreach ($this->transaction->products as $product) {
            if ($product->param('description')) {
                $this->hasdesc = true;
                break;
            }
        }
    }

    protected function setPays()
    {
        $needacceptbtn = false;
        foreach ($this->pays as &$pay) {
            if (TransactionPay::pending == $pay->status) {
                $needacceptbtn = true;
            }
            $pay->date = Date::format('Y/m/d H:i:s', $pay->date);
            $pay->price = $this->numberFormat(abs($pay->price)).' '.$pay->currency->title;
            if (TransactionPay::credit == $pay->method) {
                $pay->method = t('pay.method.credit');
            } elseif (TransactionPay::banktransfer == $pay->method) {
                if ($bankaccount = Account::byId($pay->param('bankaccount'))) {
                    $pay->method = t('pay.byBankTransfer.withbank', ['bankaccount' => $bankaccount->bank->title."[{$bankaccount->cart}]"]);
                } else {
                    $pay->method = t('pay.byBankTransfer');
                }
                $description = '';
                if ($pay->param('followup')) {
                    $description = t('pay.byBankTransfer.withfollowup', ['followup' => $pay->param('followup')]);
                }
                if ($pay->param('description')) {
                    $description .= "\n<br>".t('financial.transaction.banktransfer.description').': '.$pay->param('description');
                }
                $attachment = $pay->param('attachment');
                if ($attachment) {
                    $url = Packages::package('financial')->url($attachment);
                    $description .= "\n<br><a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-paperclip\"></i> ".t('pay.banktransfer.attachment').'</a>';
                }
                $pay->description = $description;
            } elseif (TransactionPay::onlinepay == $pay->method) {
                if ($payport_pay = PayPortPay::byId($pay->param('payport_pay'))) {
                    $pay->method = t('pay.byPayOnline.withpayport', ['payport' => $payport_pay->payport->title]);
                } else {
                    $pay->method = t('pay.byPayOnline');
                }
            } elseif (TransactionPay::payaccepted == $pay->method) {
                $acceptor = User::byId($pay->param('acceptor'));
                $pay->method = t('pay.method.payaccepted', ['acceptor' => $acceptor->getFullName()]);
            }
        }
        if ($needacceptbtn) {
            $this->setButton('pay_accept', $this->canPayAccept, [
                'title' => t('pay.accept'),
                'icon' => 'fa fa-check',
                'classes' => ['btn', 'btn-xs', 'btn-green'],
            ]);
            $this->setButton('pay_reject', $this->canPayReject, [
                'title' => t('pay.reject'),
                'icon' => 'fa fa-times',
                'classes' => ['btn', 'btn-xs', 'btn-danger'],
            ]);
        }
    }

    protected function paysHasDiscription()
    {
        foreach ($this->pays as $pay) {
            if ($pay->description) {
                return true;
            }
        }

        return false;
    }

    protected function paysHasStatus()
    {
        foreach ($this->pays as $pay) {
            if (TransactionPay::accepted != $pay->status) {
                return true;
            }
        }

        return false;
    }

    protected function getTransActionLogo()
    {
        if ($logoPath = Options::get('packages.financial.transactions_logo')) {
            return Packages::package('financial')->url($logoPath);
        }

        return null;
    }

    protected function transactionHasPaysToReimburse(Transaction $transaction): bool
    {
        return boolval(
            (new TransactionPay())
                ->where('transaction', $transaction->id)
                ->where('method',
                    [
                        TransactionPay::CREDIT,
                        TransactionPay::ONLINEPAY,
                        TransactionPay::BANKTRANSFER,
                    ],
                    'IN'
                )
                ->where('status', TransactionPay::ACCEPTED)
        ->has());
    }
}
