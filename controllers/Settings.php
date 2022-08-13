<?php

namespace packages\financial\controllers;

use packages\base\Options;
use packages\financial\Currency;
use packages\userpanel\events\General\Settings\Controller;
use packages\userpanel\events\General\Settings\Log;

class Settings implements Controller
{
    public function store(array $inputs): array
    {
        $logs = [];

        if (!isset($inputs['financial_checkout_limits'])) {
            return $logs;
        }

        $option = Options::get('packages.financial.checkout_limits') ?? [];

        $newOption = [];

        if (
            isset($option['price']) and
            isset($option['curreny']) and
            isset($option['period']) and
            $inputs['financial_checkout_limits']['curreny'] == $option['curreny'] and
            $inputs['financial_checkout_limits']['price'] == $option['price'] and
            $inputs['financial_checkout_limits']['price'] == $option['period']
        ) {
            return $logs;
        }

        Options::save('packages.financial.checkout_limits', $inputs['financial_checkout_limits']);

        $oldCurrency = isset($option['currency']) ? Currency::byId($option['currency']) : Currency::getDefault();
        $newCurrency = (isset($option['currency']) and $inputs['financial_checkout_limits']['currency'] == $option['currency']) ? $oldCurrency : Currency::byId($inputs['financial_checkout_limits']['currency']);

        if (!isset($option['price']) or $option['price'] != $inputs['financial_checkout_limits']['price']) {
            $old = $oldCurrency->format($option['price'] ?? 0);
            $new = $newCurrency->format($inputs['financial_checkout_limits']['price']);

            $logs[] = new Log(
                'financial_checkout_limits_price',
                $old,
                $new,
                t('titles.checkout_limits.price')
            );
        }

        if (!isset($option['currency']) or $option['currency'] != $inputs['financial_checkout_limits']['currency']) {
            $logs[] = new Log(
                'financial_checkout_limits_currency',
                ('#'.$oldCurrency->id.', '.$oldCurrency->title),
                ('#'.$newCurrency->id.', '.$newCurrency->title),
                t('titles.checkout_limits.currency')
            );
        }

        if (!isset($option['period']) or $option['period'] != $inputs['financial_checkout_limits']['period']) {
            $old = $option['period'] ?? '';
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
