<?php

namespace packages\financial;

use packages\base\DB\DBObject;
use packages\base\Options;
use packages\financial\Currency\Param;
use packages\financial\Currency\Rate;
use packages\financial\Currency\UnChangableException;
use packages\financial\Currency\UndefinedCurrencyException;
use packages\userpanel\User;

class Currency extends DBObject
{
    use Paramable;

    /** rounding-behaviour */
    public const CEIL = 1;
    public const ROUND = 2;
    public const FLOOR = 3;

    public static function getDefault(?User $user = null): Currency
    {
        $currencyID = null;
        if ($user) {
            $currencyID = $user->option('financial_transaction_currency');
        }
        if (!$currencyID) {
            $currencyID = Options::get('packages.financial.defaultCurrency');
            if (!$currencyID) {
                throw new UndefinedCurrencyException();
            }
            if ($user) {
                $user->option('financial_transaction_currency', $currencyID);
            }
        }

        return (new Currency())->byID($currencyID);
    }

    protected $dbTable = 'financial_currencies';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'prefix' => ['type' => 'text'],
        'title' => ['type' => 'text', 'required' => true],
        'postfix' => ['type' => 'text'],
        'update_at' => ['type' => 'int', 'required' => true],
        'rounding_behaviour' => ['type' => 'int'],
        'rounding_precision' => ['type' => 'int'],
    ];
    protected $relations = [
        'params' => ['hasMany', Param::class, 'currency'],
        'rates' => ['hasMany', Rate::class, 'currency'],
    ];

    public function format(float $amount): string
    {
        return $this->prefix.number_format($amount, $this->rounding_precision).$this->postfix.((!$this->prefix and !$this->postfix) ? ' '.$this->title : '');
    }

    public function addRate(Currency $currency, float $price): void
    {
        $rate = $this->getRate($currency->id);
        if ($rate) {
            $rate->price = $price;
            $rate->save();
        } else {
            $rate = new Rate();
            $rate->currency = $this->id;
            $rate->changeTo = $currency->id;
            $rate->price = $price;
            $rate->save();
        }
    }

    public function deleteRate(int $rate = 0): void
    {
        if (0 == $rate) {
            foreach ($this->rates as $rate) {
                $rate->delete();
            }
        } else {
            $rate = (new Rate())->byID($rate);
            if ($rate) {
                $rate->delete();
            }
        }
    }

    public function hasRate(int $with = 0): bool
    {
        $rate = new Rate();
        $rate->where('currency', $this->id);
        if ($with > 0) {
            $rate->where('changeTo', $with);
        }

        return $rate->has();
    }

    public function getCountRates(): int
    {
        return (new Rate())->where('currency', $this->id)->count();
    }

    public function getRate(int $changeTo): ?Rate
    {
        return (new Rate())->where('currency', $this->id)->where('changeTo', $changeTo)->getOne();
    }

    public function round(float $amount): float
    {
        switch ($this->rounding_behaviour) {
            case self::CEIL:
                $precision = pow(10, $this->rounding_precision);
                $amount = ceil($amount * $precision);
                $amount /= $precision;
                break;
            case self::ROUND:
                $amount = round($amount, $this->rounding_precision);
                break;
            case self::FLOOR:
                $precision = pow(10, $this->rounding_precision);
                $amount = floor($amount * $precision);
                $amount /= $precision;
                break;
        }

        return floatval($amount);
    }

    public function changeTo(float $price, Currency $other): float
    {
        if ($other->id == $this->id) {
            return $price;
        }
        $rate = $this->getRate($other->id);
        if (!$rate) {
            throw new UnChangableException($other, $this);
        }
        $changed = $price * $rate->price;

        return $other->round($changed);
    }
}
