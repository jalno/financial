<?php

namespace themes\clipone\Views\Transactions;

use packages\financial\Bank\Account;
use packages\financial\PayPortPay;
use packages\financial\Transaction;
use packages\financial\TransactionPay;
use packages\financial\Views\Transactions\Edit as TransactionsEdit;
use packages\userpanel;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\Views\TransactionTrait;
use themes\clipone\ViewTrait;

class Edit extends TransactionsEdit
{
    use ViewTrait;
    use FormTrait;
    use ListTrait;
    use TransactionTrait;
    protected $transaction;
    protected $pays;

    public function __beforeLoad()
    {
        $this->transaction = $this->getTransactionData();
        $this->setTitle([
            t('edit'),
            $this->transaction->id,
        ]);
        $this->setShortDescription(t('transaction.edit'));
        $this->setPays();
        $this->setNavigation();
        $this->setButtons();
        $this->setForm();
        $this->addBodyClass('transaction-edit');
    }

    private function setNavigation()
    {
        Navigation::active('transactions/list');
    }

    protected function setPays()
    {
        $needacceptbtn = false;
        $this->pays = $this->transaction->pays;
        foreach ($this->pays as $pay) {
            if (TransactionPay::pending == $pay->status) {
                $needacceptbtn = true;
            }
            switch ($pay->method) {
                case TransactionPay::credit:
                    $pay->method = t('pay.method.credit');
                    break;
                case TransactionPay::banktransfer:
                    if ($bankaccount = Account::byId($pay->param('bankaccount'))) {
                        $pay->method = t('pay.byBankTransfer.withbank', ['bankaccount' => $bankaccount->cart]);
                    } else {
                        $pay->method = t('pay.byBankTransfer');
                    }
                    $pay->description = t('pay.byBankTransfer.withfollowup', ['followup' => $pay->param('followup')]);
                    break;
                case TransactionPay::onlinepay:
                    if ($payport_pay = PayPortPay::byId($pay->param('payport_pay'))) {
                        $pay->method = t('pay.byPayOnline.withpayport', ['payport' => $payport_pay->payport->title]);
                    } else {
                        $pay->method = t('pay.byPayOnline');
                    }
                    break;
                case TransactionPay::payaccepted:
                    $acceptor = userpanel\User::byId($pay->param('acceptor'));
                    $pay->method = t('pay.method.payaccepted', ['acceptor' => $acceptor->getFullName()]);
                    break;
            }
        }
        if ($needacceptbtn) {
            $this->setButton('pay_accept', $this->canPayAccept and Transaction::unpaid == $this->transaction->status, [
                'title' => t('pay.accept'),
                'icon' => 'fa fa-check',
                'classes' => ['btn', 'btn-xs', 'btn-green'],
            ]);
            $this->setButton('pay_reject', $this->canPayReject and Transaction::unpaid == $this->transaction->status, [
                'title' => t('pay.reject'),
                'icon' => 'fa fa-times',
                'classes' => ['btn', 'btn-xs', 'btn-tael'],
            ]);
        }
    }

    protected function paysHasDiscription()
    {
        foreach ($this->getTransactionData()->pays as $pay) {
            if ($pay->description) {
                return true;
            }
        }

        return false;
    }

    protected function paysHasStatus()
    {
        foreach ($this->getTransactionData()->pays as $pay) {
            if (TransactionPay::accepted != $pay->status) {
                return true;
            }
        }

        return false;
    }

    public function setButtons()
    {
        $this->setButton('productEdit', $this->canEditProduct, [
            'title' => t('financial.edit'),
            'icon' => 'fa fa-edit ',
            'classes' => ['btn', 'btn-xs', 'btn-teal', 'product-edit'],
            'data' => [
                'toggle' => 'modal',
            ],
        ]);
        $this->setButton('productDelete', $this->canDeleteProduct, [
            'title' => t('financial.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky', 'product-delete'],
        ]);
        $this->setButton('pay_delete', $this->canPaydelete, [
            'title' => t('financial.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
        $this->setButton('pay_edit', $this->canEditPays, [
            'title' => t('financial.edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-teal'],
            'data' => [
                'action' => 'edit',
            ],
        ]);
    }

    private function setForm()
    {
        if ($user = $this->getDataForm('user')) {
            if ($user = userpanel\User::byId($user)) {
                $this->setDataForm($user->getFullName(), 'user_name');
            }
        }
    }

    protected function getCurrenciesForSelect(): array
    {
        $currencies = [];
        foreach ($this->getCurrencies() as $currency) {
            $currencies[] = [
                'title' => $currency->title,
                'value' => $currency->id,
                'data' => [
                    'title' => $currency->title,
                ],
            ];
        }

        return $currencies;
    }
}
