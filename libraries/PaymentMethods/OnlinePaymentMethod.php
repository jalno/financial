<?php

namespace packages\financial\PaymentMethdos;

use packages\base\DB;
use packages\base\DB\Parenthesis;
use packages\base\Options;
use packages\financial\Contracts\IPaymentMethod;
use packages\financial\Payport;
use packages\financial\Transaction;
use packages\financial\Transaction_pay as TransactionPay;
use packages\financial\TransactionManager;

class OnlinePaymentMethod implements IPaymentMethod
{
    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    private static ?self $instance = null;

    public function getName(): string
    {
        return 'onlinepay';
    }

    public function getIcon(): string
    {
        return 'fa fa-credit-card';
    }

    public function canPay(int|Transaction $transactionId): bool
    {
        $transaction = $transactionId instanceof Transaction ?
            $transactionId :
            TransactionManager::getInstance()->getForPayById($transactionId);

        if ($transaction->remainPriceForAddPay() <= 0) {
            return false;
        }

        $payportIDs = $transaction->param('available_online_payports');
        if (!$payportIDs) {
            $payportIDs = Options::get('packages.financial.available_online_payports');
        }

        $currency = $transaction->currency;

        $parenthesis = new Parenthesis();
        $parenthesis->where('financial_payports_currencies.currency', $currency->id);
        $parenthesis->orWhere('financial_currencies_rates.changeTo', $currency->id);

        DB::join('financial_payports_currencies', 'financial_payports_currencies.payport=financial_payports.id', 'INNER');
        DB::join('financial_currencies', 'financial_currencies.id=financial_payports_currencies.currency', 'LEFT');
        DB::join('financial_currencies_rates', 'financial_currencies_rates.currency=financial_currencies.id', 'LEFT');

        $query = new Payport();
        $query->where($parenthesis);
        $query->where('financial_payports.status', Payport::active);
        if ($payportIDs) {
            $query->where('financial_payports.id', $payportIDs, 'IN');
        }
        $query->setQueryOption('DISTINCT');

        return $query->has();
    }

    public function getPayTitle(TransactionPay $pay): string
    {
        return t("pay.method.credit");
    }

    public function getPayports(int|Transaction $transactionId): array
    {
        $transaction = $transactionId instanceof Transaction ?
            $transactionId :
            TransactionManager::getInstance()->getForPayById($transactionId);

        $payportIDs = $transaction->param('available_online_payports');

        if (!$payportIDs) {
            $payportIDs = Options::get('packages.financial.available_online_payports');
        }

        $currency = $transaction->currency;

        $parenthesis = new Parenthesis();
        $parenthesis->where('financial_payports_currencies.currency', $currency->id);
        $parenthesis->orWhere('financial_currencies_rates.changeTo', $currency->id);

        DB::join('financial_payports_currencies', 'financial_payports_currencies.payport=financial_payports.id', 'INNER');
        DB::join('financial_currencies', 'financial_currencies.id=financial_payports_currencies.currency', 'LEFT');
        DB::join('financial_currencies_rates', 'financial_currencies_rates.currency=financial_currencies.id', 'LEFT');

        $query = new Payport();
        $query->where($parenthesis);
        $query->where('financial_payports.status', Payport::active);
        if ($payportIDs) {
            $query->where('financial_payports.id', $payportIDs, 'IN');
        }
        $query->setQueryOption('DISTINCT');

        return $query->get(null, 'financial_payports.*');
    }
}
