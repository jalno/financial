<?php

namespace packages\financial;

use packages\base\DB;
use packages\base\DB\Parenthesis;
use packages\base\Options;
use packages\financial\Bank\Account;
use packages\financial\Contracts\IFinancialService;
use packages\financial\Contracts\ITransactionManager;
use packages\userpanel\User;

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
        return in_array(Transaction::ONLINE_PAYMENT_METHOD, $this->getPaymentMethods($id)) and
            !empty($this->getOnlinePayports($id));
    }

    /**
     * @return Payport[]
     */
    public function getOnlinePayports(int $id): array
    {
        if (!in_array(Transaction::ONLINE_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            throw new \Exception('Can not pay with online payports');
        }

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

    public function canPayByTransferBank(int $id): bool
    {
        return in_array(Transaction::BANK_TRANSFER_PAYMENT_METHOD, $this->getPaymentMethods($id)) and
            !empty($this->getBankAccountsForTransferPay($id));
    }

    public function getBankAccountsForTransferPay(int $id): array
    {
        if (!in_array(Transaction::BANK_TRANSFER_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            throw new \Exception('Can not pay with bank transfer');
        }

        $transaction = $this->getByID($id);

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

    public function canPayByCredit(int $id, ?int $operatorID = null): bool
    {
        if (!in_array(Transaction::CREDIT_PAYMENT_METHOD, $this->getPaymentMethods($id))) {
            return false;
        }

        $transaction = $this->getByID($id);

        $operator = null;
        if ($operatorID) {
            $operator = (new User())->byId($operatorID);

            if (!$operator) {
                throw new \Exception('Can not fin operator by id: '.$operatorID);
            }
        }

        if ((!$operator or $operator->credit <= 0) and $transaction->user->credit <= 0) {
            return false;
        }

        return $transaction->canPayByCredit();
    }

    public function getAvailablePaymentMethods(int $id, ?int $operatorID = null): array
    {
        return array_filter($this->getPaymentMethods($id), function (string $method) use ($id, $operatorID) {
            switch ($method) {
                case Transaction::CREDIT_PAYMENT_METHOD:
                    return $this->canPayByCredit($id, $operatorID);
                case Transaction::BANK_TRANSFER_PAYMENT_METHOD:
                    return $this->canPayByTransferBank($id);
                case Transaction::ONLINE_PAYMENT_METHOD:
                    return $this->canOnlinePay($id);
                default:
                    return false;
            }
        });
    }

    public function getPaymentMethods($id): array
    {
        $transaction = $this->getByID($id);

        $methods = $transaction->param('available_payment_methods');

        if (!is_array($methods)) {
            $methods = [
                Transaction::CREDIT_PAYMENT_METHOD,
                Transaction::BANK_TRANSFER_PAYMENT_METHOD,
                Transaction::ONLINE_PAYMENT_METHOD,
            ];
        }

        return $methods;
    }
}
