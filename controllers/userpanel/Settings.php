<?php

namespace packages\financial\controllers\userpanel;

use packages\base\DB;
use packages\base\InputValidationException;
use packages\base\Log as BaseLog;
use packages\base\Options;
use packages\base\view\Error;
use packages\financial\Authorization;
use packages\financial\Currency;
use packages\userpanel\events\settings\Controller;
use packages\userpanel\events\settings\Log;
use packages\userpanel\user;

class Settings implements Controller
{
    public function store(array $inputs, user $user): array
    {
        return array_merge(
            $this->handleStoreCurrency($inputs, $user),
            $this->handleChangeCheckoutLimits($inputs, $user),
        );
    }

    /**
     * handle change currency of a user in profile settings or user settings.
     *
     * @param array $inputs that may contain 'financial_transaction_currency' index
     * @param User  $user   that is the user we do action on him/her
     *
     * @return Log[] that is the log(s) of the action
     */
    protected function handleStoreCurrency(array $inputs, User $user): array
    {
        $logs = [];

        $canChangeCurrency = Authorization::is_accessed('profile_change_currency');
        if (
            !$canChangeCurrency or
            !isset($inputs['financial_transaction_currency'])
        ) {
            return $logs;
        }

        $newCurrency = (new Currency())->byID($inputs['financial_transaction_currency']);
        if (!$newCurrency) {
            throw new InputValidationException('financial_transaction_currency');
        }

        $currency = Currency::getDefault($user);

        if ($newCurrency->id != $currency->id) {
            $log = BaseLog::getInstance();
            $log->info('change currency of user: #'.$user->id);

            $freshUser = (new User())->byID($user->id);
            $oldCredit = $freshUser->credit;

            $log->info('the old credit of user: #'.$user->id.' is: '.$oldCredit.' '.$currency->title);

            $log->info('try to change credit of user: #'.$user->id);
            $user->credit = $currency->changeTo($oldCredit, $newCurrency);
            $log->reply('done, new credit is:', $user->credit);

            $log->info('save user: #'.$user->id);
            $saveUserResult = $user->save();
            if ($saveUserResult) {
                $log->reply('done');
            } else {
                $log->reply()->error(
                    'can not save user!',
                    'DB::getLastError:', DB::getLastError(),
                    'DB::getLastQuery:', DB::getLastQuery()
                );
                $error = new Error('packages.financial.controller.userpanel.settings.change_currency_failed');
                $error->setMessage("error.{$error->getCode()}");
                throw $error;
            }

            $log->info('set user: #'.$user->id.' financial_transaction_currency option to:', $newCurrency->id);
            $setOptionResult = $user->setOption(
                'financial_transaction_currency',
                $newCurrency->id
            );
            if ($setOptionResult) {
                $log->reply('done');
            } else {
                $log->reply()->warn(
                    'faild!',
                    'DB::getLastError:', DB::getLastError(),
                    'DB::getLastQuery:', DB::getLastQuery()
                );

                $log->warn('change user: #'.$user->id.' credit to old credit');
                $user->credit = $oldCredit;
                $saveUserResult = $user->save();
                if (!$saveUserResult) {
                    $log->reply()->warn(
                        'faild!',
                        'DB::getLastError:', DB::getLastError(),
                        'DB::getLastQuery:', DB::getLastQuery()
                    );
                } else {
                    $log->reply('done');
                }

                $error = new Error('packages.financial.controller.userpanel.settings.change_currency_failed');
                $error->setMessage("error.{$error->getCode()}");
                throw $error;
            }

            $logs[] = new Log(
                'financial_transaction_currency',
                $currency->title,
                $newCurrency->title,
                t('financial.usersettings.transaction.currency')
            );

            $freshUser = (new User())->byID($user->id);
            $logs[] = new Log(
                'financial_transaction_currency_credit',
                $oldCredit,
                $freshUser->credit,
                t('user.credit')
            );
        }

        return $logs;
    }

    protected function handleChangeCheckoutLimits(array $inputs, User $user): array
    {
        $logs = [];

        if (
            !Authorization::is_accessed('profile_checkout_limits') or
            !isset($inputs['financial_checkout_limits'])
        ) {
            return $logs;
        }

        $option = Options::get('packages.financial.checkout_limits') ?: [];
        $userOption = $user->option('financial_checkout_limits') ?: [];

        $newOption = [];

        if (
            isset($option['price'], $option['currency'], $option['period']) and
            $inputs['financial_checkout_limits']['currency'] == $option['currency'] and
            $inputs['financial_checkout_limits']['price'] == $option['price'] and
            $inputs['financial_checkout_limits']['period'] == $option['period']
        ) {
            $query = new User\Option();
            $query->where('user', $user->id);
            $query->where('name', 'financial_checkout_limits');
            $userOptionModel = $query->getOne();
            if ($userOptionModel) {
                $userOptionModel->delete();
            }
        }

        if (
            isset($userOption['price'], $userOption['currency'], $userOption['period']) and
            $inputs['financial_checkout_limits']['currency'] == $userOption['currency'] and
            $inputs['financial_checkout_limits']['price'] == $userOption['price'] and
            $inputs['financial_checkout_limits']['period'] == $userOption['period']
        ) {
            return $logs;
        }

        $oldOption = $userOption;
        if (!$oldOption) {
            $oldOption = $option;
        }

        $user->setOption('financial_checkout_limits', $inputs['financial_checkout_limits']);

        $oldCurrency = isset($oldOption['currency']) ? Currency::byId($oldOption['currency']) : Currency::getDefault();
        $newCurrency = (isset($oldOption['currency']) and $inputs['financial_checkout_limits']['currency'] == $oldOption['currency']) ? $oldCurrency : Currency::byId($inputs['financial_checkout_limits']['currency']);

        if (!isset($oldOption['price']) or $oldOption['price'] != $inputs['financial_checkout_limits']['price']) {
            $old = $oldCurrency->format($oldOption['price'] ?? 0);
            $new = $newCurrency->format($inputs['financial_checkout_limits']['price']);

            $logs[] = new Log(
                'financial_checkout_limits_price',
                $old,
                $new,
                t('titles.checkout_limits.price')
            );
        }

        if (!isset($oldOption['currency']) or $oldOption['currency'] != $inputs['financial_checkout_limits']['currency']) {
            $logs[] = new Log(
                'financial_checkout_limits_currency',
                ('#'.$oldCurrency->id.', '.$oldCurrency->title),
                ('#'.$newCurrency->id.', '.$newCurrency->title),
                t('titles.checkout_limits.currency')
            );
        }

        if (!isset($oldOption['period']) or $oldOption['period'] != $inputs['financial_checkout_limits']['period']) {
            $old = $oldOption['period'] ?? 0;
            if ($old) {
                $old /= 86400;
            }

            $new = $inputs['financial_checkout_limits']['period'] / 86400;

            $logs[] = new Log(
                'financial_checkout_limits_period',
                $old.' '.t('titles.day'),
                $new.' '.t('titles.day'),
                t('titles.checkout_limits.period')
            );
        }

        return $logs;
    }
}
