<?php

namespace packages\financial\PaymentMethdos;

use packages\base\Options;
use packages\base\Packages;
use packages\financial\Bank\Account;
use packages\financial\Contracts\IPaymentMethod;
use packages\financial\Transaction;
use packages\financial\Transaction_pay as TransactionPay;
use packages\financial\TransactionManager;

class BankTransferPaymentMethod implements IPaymentMethod
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;

    public function getName(): string
    {
        return 'banktransfer';
    }

    public function getIcon(): string
    {
        return 'fa fa-university';
    }

    public function canPay(int|Transaction $transactionId): bool
    {
        $transaction = $transactionId instanceof Transaction ?
            $transactionId :
            TransactionManager::getInstance()->getForPayById($transactionId);

        $bankAccountIDs = $transaction->param('available_bank_accounts');
        if (!$bankAccountIDs) {
            $bankAccountIDs = Options::get('packages.financial.pay.tansactions.banka.accounts');
        }

        $query = new Account();
        $query->with('bank');
        $query->where('financial_banks_accounts.status', Account::Active);
        if ($bankAccountIDs) {
            $query->where('financial_banks_accounts.id', $bankAccountIDs, 'IN');
        }

        return $query->has();
    }

    public function getPayTitle(TransactionPay $pay): string
    {
        $bankaccount = Account::byId($pay->param("bankaccount"));

        $title = '';
        if ($bankaccount) {
            $title = t("pay.byBankTransfer.withbank", array("bankaccount" => $bankaccount->bank->title . "[{$bankaccount->cart}]"));
        } else {
            $title = t("pay.byBankTransfer");
        }

        $description = "";
        if ($pay->param("followup")) {
            $description = t("pay.byBankTransfer.withfollowup", array("followup" => $pay->param("followup")));
        }
        if ($pay->param("description")) {
            $description .= "\n<br>" . t("financial.transaction.banktransfer.description") . ": " . $pay->param("description");
        }

        $attachment = $pay->param("attachment");
        if ($attachment) {
            $url = Packages::package("financial")->url($attachment);
            $description .= "\n<br><a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-paperclip\"></i> " . t("pay.banktransfer.attachment") . "</a>";
        }


        return $title."\n<br>".$description;
    }

    /**
     * @return Account[]
     */
    public function getBankAccountsForPay(int|Transaction $transactionId): array
    {
        $transaction = $transactionId instanceof Transaction ?
            $transactionId :
            TransactionManager::getInstance()->getForPayById($transactionId);

        $bankAccountIDs = $transaction->param('available_bank_accounts');

        if (!$bankAccountIDs) {
            $bankAccountIDs = Options::get('packages.financial.pay.tansactions.banka.accounts');
        }

        $query = new Account();
        $query->with('bank');
        $query->where('financial_banks_accounts.status', Account::Active);
        if ($bankAccountIDs) {
            $query->where('financial_banks_accounts.id', $bankAccountIDs, 'IN');
        }

        return $query->get();
    }
}
