<?php

namespace packages\financial;

use packages\base\DB;
use packages\base\DB\Parenthesis;
use packages\base\Options;
use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\ITransactionManager;

class TransactionManager implements ITransactionManager
{
    public IFinancialService $serviceProvider;

    public function __construct(IFinancialService $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function getByID(int $id): Transaction
    {
        $transaction = (new Transaction())->byId($id);

        if (!$transaction) {
            throw new \Exception('Can not find any transaction with id: '.$id);
        }

        return $transaction;
    }

    public function canOnlinePay(int $id): bool
    {
        return !empty($this->getOnlinePayports($id));
    }

    /**
     * @return Payport[]
     */
    public function getOnlinePayports(int $id): array
    {
        $transaction = $this->getByID($id);

        $payportIDs = $transaction->param('available_online_payports');

        if (!$payportIDs) {
            $payportIDs = Options::get('packages.financial.available_online_payports');
        }

        $currency = $transaction->currency;

        DB::join('financial_payports_currencies', 'financial_payports_currencies.payport=financial_payports.id', 'INNER');
        DB::join('financial_currencies', 'financial_currencies.id=financial_payports_currencies.currency', 'LEFT');
        DB::join('financial_currencies_rates', 'financial_currencies_rates.currency=financial_currencies.id', 'LEFT');

        $query = new Payport();

        $parenthesis = new Parenthesis();
        $parenthesis->where('financial_payports_currencies.currency', $currency->id);
        $parenthesis->orWhere('financial_currencies_rates.changeTo', $currency->id);

        $query->where($parenthesis);

        $query->where('financial_payports.status', Payport::active);

        if ($payportIDs) {
            $query->where('financial_payports.id', $payportIDs, 'IN');
        }

        $query->setQueryOption('DISTINCT');
        $payports = $query->get(null, 'financial_payports.*');

        return $payports;
    }
}
