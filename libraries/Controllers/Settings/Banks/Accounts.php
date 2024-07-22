<?php

namespace packages\financial\Controllers\Settings\Banks;

use packages\base\DB\Parenthesis;
use packages\base\InputValidation;
use packages\base\NotFound;
use packages\base\Response;
use packages\base\View\Error;
use packages\financial\Authentication;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\financial\Bank\Account;
use packages\financial\Controller;
use packages\financial\PayPort;
use packages\financial\Validators;
use packages\financial\View;
use themes\clipone\Views\Email as Views;
use packages\userpanel;

class Accounts extends Controller
{
    protected $authentication = true;

    public function search(): Response
    {
        Authorization::haveOrFail('settings_banks_accounts_search');
        $view = View::byName(Views\Settings\Banks\Accounts\Search::class);
        $types = Authorization::childrenTypes();
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $inputsRules = [
            'id' => [
                'type' => 'number',
                'optional' => true,
                'empty' => true,
            ],
            'bank' => [
                'type' => 'number',
                'optional' => true,
                'empty' => true,
            ],
            'user' => [
                'type' => 'number',
                'optional' => true,
                'empty' => true,
            ],
            'account' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'cart' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'shaba' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'owner' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'status' => [
                'values' => [Account::Active, Account::WaitForAccept, Account::Rejected, Account::Deactive],
                'optional' => true,
                'empty' => true,
            ],
            'word' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'comparison' => [
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ];
        $inputs = $this->checkinputs($inputsRules);
        foreach (array_keys($inputsRules) as $item) {
            if (isset($inputs[$item]) and '' == $inputs[$item]) {
                unset($inputs[$item]);
            }
        }
        foreach (['id', 'bank', 'user', 'owner', 'account', 'cart', 'shaba', 'status'] as $item) {
            if (isset($inputs[$item])) {
                $comparison = $inputs['comparison'];
                $key = $item;
                if (in_array($item, ['id', 'bank', 'user'])) {
                    $comparison = 'equals';
                    if ('id' != $item) {
                        $key .= '_id';
                    }
                }
                $account->where("financial_banks_accounts.{$key}", $inputs[$item], $comparison);
            }
        }
        if (isset($inputs['word'])) {
            $parenthesis = new Parenthesis();
            foreach (['owner', 'account', 'cart', 'shaba'] as $item) {
                if (!isset($inputs[$item])) {
                    $parenthesis->orWhere("financial_banks_accounts.{$item}", $inputs[$item], $inputs['comparison']);
                }
            }
            if (!$parenthesis->isEmpty()) {
                $account->where($parenthesis);
            }
        }
        $account->orderBy('financial_banks_accounts.id', 'DESC');
        $accounts = $account->paginate($this->page, ['financial_banks_accounts.*', 'financial_banks.*', 'userpanel_users.*']);
        $this->total_pages = $account->totalPages;
        $view->setDataList($accounts);
        $view->setPaginate($this->page, $account->totalCount, $this->items_per_page);
        $this->response->setStatus(true);
        $this->response->setView($view);

        return $this->response;
    }

    public function add()
    {
        Authorization::haveOrFail('settings_banks_accounts_add');
        $view = View::byName(Views\Settings\Banks\Accounts\Add::class);
        $this->response->setView($view);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function store()
    {
        Authorization::haveOrFail('settings_banks_accounts_add');
        $view = View::byName(Views\Settings\Banks\Accounts\Add::class);
        $this->response->setView($view);
        $inputsRules = [
            'bank' => [
                'type' => 'number',
            ],
            'user' => [
                'type' => 'number',
                'optional' => true,
            ],
            'owner' => [
                'type' => 'string',
            ],
            'account' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'cart' => [
                'regex' => '/^[0-9]{16,19}$/',
            ],
            'shaba' => [
                'type' => Validators\IBANValidator::class,
            ],
        ];
        $this->response->setStatus(false);
        $inputs = $this->checkinputs($inputsRules);
        if (!Authorization::childrenTypes()) {
            unset($inputs['user']);
        }
        if (isset($inputs['user'])) {
            if (!userpanel\User::byId($inputs['user'])) {
                throw new InputValidation('user');
            }
        } else {
            $inputs['user'] = Authentication::getID();
        }
        $bank = new Bank();
        $bank->where('status', Bank::Active);
        if (!$bank->byId($inputs['bank'])) {
            throw new InputValidation('bank');
        }
        $account = new Account();
        foreach (['bank', 'user'] as $item) {
            $account->{$item.'_id'} = $inputs[$item];
        }
        foreach (['owner', 'cart', 'shaba'] as $item) {
            $account->$item = $inputs[$item];
        }
        $account->account = (isset($inputs['account']) and $inputs['account']) ? $inputs['account'] : null;
        $account->save();
        $this->response->setStatus(true);
        $this->response->GO(userpanel\url('settings/financial/banks/accounts'));

        return $this->response;
    }

    public function edit(array $data)
    {
        Authorization::haveOrFail('settings_banks_accounts_edit');
        $types = Authorization::childrenTypes();
        $canAccept = Authorization::is_accessed('settings_banks_accounts_accept');
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        if (!$canAccept) {
            $account->where('financial_banks_accounts.status', Account::Rejected);
        }
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $view = View::byName(Views\Settings\Banks\Accounts\Edit::class);
        $this->response->setView($view);
        $view->setBankaccount($account);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function update(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_accounts_edit');
        $types = Authorization::childrenTypes();
        $canAccept = Authorization::is_accessed('settings_banks_accounts_accept');
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        if (!$canAccept) {
            $account->where('financial_banks_accounts.status', Account::Rejected);
        }
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $view = View::byName(Views\Settings\Banks\Accounts\Edit::class);
        $this->response->setView($view);
        $view->setBankaccount($account);
        $inputsRules = [
            'bank' => [
                'type' => 'number',
                'optional' => true,
            ],
            'user' => [
                'type' => 'number',
                'optional' => true,
            ],
            'owner' => [
                'type' => 'string',
                'optional' => true,
            ],
            'account' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'cart' => [
                'regex' => '/^[0-9]{16,19}$/',
                'optional' => true,
            ],
            'shaba' => [
                'type' => Validators\IBANValidator::class,
                'optional' => true,
            ],
        ];
        $this->response->setStatus(false);
        $inputs = $this->checkinputs($inputsRules);
        if (!Authorization::childrenTypes()) {
            unset($inputs['user']);
        }
        if (isset($inputs['user'])) {
            if ($inputs['user']) {
                if (!userpanel\User::byId($inputs['user'])) {
                    throw new InputValidation('user');
                }
            } else {
                unset($inputs['user']);
            }
        }
        $hasChange = false;
        foreach (['bank', 'user'] as $item) {
            $key = $item.'_id';
            if (isset($inputs[$item]) and $account->$key != $inputs[$item]) {
                $account->$key = $inputs[$item];
                $hasChange = true;
            }
        }
        foreach (['owner', 'cart', 'shaba'] as $item) {
            if (isset($inputs[$item]) and $account->$item != $inputs[$item]) {
                $account->$item = $inputs[$item];
                $hasChange = true;
            }
        }
        $account->account = (isset($inputs['account']) and $inputs['account']) ? $inputs['account'] : null;
        if ($hasChange and !$canAccept) {
            $account->status = Account::WaitForAccept;
        }
        $account->save();
        $this->response->setStatus(true);
        if ($canAccept) {
            $this->response->GO(userpanel\url('settings/financial/banks/accounts/edit/'.$account->id));
        } else {
            $this->response->GO(userpanel\url('settings/financial/banks/accounts'));
        }

        return $this->response;
    }

    public function delete(array $data)
    {
        Authorization::haveOrFail('settings_banks_accounts_delete');
        $types = Authorization::childrenTypes();
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $view = View::byName(Views\Settings\Banks\Accounts\Delete::class);
        $view->setBankaccount($account);
        $this->response->setStatus(true);
        $this->response->setView($view);

        return $this->response;
    }

    public function terminate(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_accounts_delete');
        $types = Authorization::childrenTypes();
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $view = View::byName(Views\Settings\Banks\Accounts\Delete::class);
        $view->setBankaccount($account);
        $this->response->setStatus(false);
        try {
            $payport = new PayPort();
            $payport->where('account', $account->id);
            $payport->where('status', PayPort::active);
            if ($payport->has()) {
                throw new PayPortDependencies();
            }
            $account->delete();
            $this->response->setStatus(true);
            $this->response->GO(userpanel\url('settings/financial/banks/accounts'));
        } catch (PayPortDependencies $error) {
            $error = new Error();
            $error->setType(Error::FATAL);
            $error->setCode('financial.settings.Account.gatewayDependencies');
            $view->addError($error);
        }

        return $this->response;
    }

    public function accept(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_accounts_accept');
        $types = Authorization::childrenTypes();
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        $account->where('financial_banks_accounts.status', Account::Active, '!=');
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $account->status = Account::Active;
        $account->reject_reason = null;
        $account->oprator_id = Authentication::getID();
        $account->save();
        $this->response->setStatus(true);
        $this->response->Go(userpanel\url('settings/financial/banks/accounts'));

        return $this->response;
    }

    public function reject(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_accounts_accept');
        $types = Authorization::childrenTypes();
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.id', $data['account']);
        $account->where('financial_banks_accounts.status', Account::Rejected, '!=');
        if (!$account = $account->getOne(['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*'])) {
            throw new NotFound();
        }
        $inputs = $this->checkinputs([
            'reason' => [
                'type' => 'string',
            ],
        ]);
        $account->status = Account::Rejected;
        $account->reject_reason = $inputs['reason'];
        $account->oprator_id = Authentication::getID();
        $account->save();
        $this->response->setStatus(true);
        $this->response->Go(userpanel\url('settings/financial/banks/accounts'));

        return $this->response;
    }
}

class payportDependencies extends \Exception
{
}
