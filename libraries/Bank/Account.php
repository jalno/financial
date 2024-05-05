<?php

namespace packages\financial\Bank;

use packages\base\DB\DBObject;
use packages\base\Options;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\userpanel\User;

class Account extends DBObject
{
    /**
     * get available accounts
     * use 'packages.financial.pay.tansactions.banka.accounts' Option to get just admin accounts.
     *
     * @return packages\financial\Bank\Account[]
     */
    public static function getAvailableAccounts($limit = null): array
    {
        $availableBankAccountsForPay = Options::get('packages.financial.pay.tansactions.banka.accounts');
        $accounts = new self();
        $accounts->with('user');
        $accounts->with('bank');
        $accounts->where('financial_banks_accounts.status', Account::Active);
        if ($availableBankAccountsForPay) {
            $accounts->where('financial_banks_accounts.id', $availableBankAccountsForPay, 'IN');
        }

        return $accounts->get($limit, ['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*']);
    }

    /** status */
    public const Active = 1;
    public const WaitForAccept = 2;
    public const Rejected = 3;
    public const Deactive = 4;

    protected $dbTable = 'financial_banks_accounts';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'bank_id' => ['type' => 'text', 'required' => true],
        'user_id' => ['type' => 'int', 'required' => true],
        'owner' => ['type' => 'text', 'required' => true],
        'account' => ['type' => 'text'],
        'cart' => ['type' => 'text'],
        'shaba' => ['type' => 'text'],
        'oprator_id' => ['type' => 'int'],
        'reject_reason' => ['type' => 'text'],
        'status' => ['type' => 'int', 'required' => true],
    ];
    protected $relations = [
        'bank' => ['hasOne', Bank::class, 'bank_id'],
        'user' => ['hasOne', User::class, 'user_id'],
    ];

    protected function preLoad(array $data): array
    {
        if (!isset($data['status'])) {
            $data['status'] = Authorization::is_accessed('settings_banks_accounts_accept') ? self::Active : self::WaitForAccept;
        }

        return $data;
    }
}
