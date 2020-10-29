<?php
namespace packages\financial;

use packages\base\{db, db\dbObject, Options};
use packages\financial\Currency\{Param, Rate, UnChangableException, UndefinedCurrencyException};
use packages\userpanel\User;

class Currency extends dbObject {
	use Paramable;

	/** rounding-behaviour */
	const CEIL = 1;
	const ROUND = 2;
	const FLOOR = 3;

	public static function getDefault(User $user = null): Currency {
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

	protected $dbTable = "financial_currencies";
	protected $primaryKey = "id";
	protected $dbFields = [
        'title' => ['type' => 'text', 'required' => true],
		'update_at' => ['type' => 'int', 'required' => true],
		'rounding_behaviour' => ['type' => 'int'],
		'rounding_precision' => ['type' => 'int'],
    ];
	protected $relations = [
		'params' => ['hasMany', Param::class, 'currency'],
		'rates' => ['hasMany', Rate::class, 'currency'],
	];
	public function addRate(Currency $currency, float $price): void {
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
	public function deleteRate(int $rate = 0): void {
		if ($rate == 0) {
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
	public function hasRate(int $with = 0): bool {
		$rate = new Rate();
		$rate->where('currency', $this->id);
		if ($with > 0) {
			$rate->where('changeTo', $with);
		}
		return $rate->has();
	}
	public function getCountRates(): int {
		return (new Rate())->where('currency', $this->id)->count();
	}
	public function getRate(int $changeTo):? Rate {
		return (new Rate())->where('currency', $this->id)->where('changeTo', $changeTo)->getOne();
	}
	public function changeTo(float $price, Currency $other): float {
		if ($other->id == $this->id) {
			return $price;
		}
		$rate = $this->getRate($other->id);
		if (!$rate) {
			throw new UnChangableException($other, $this);
		}
		$changed = $price * $rate->price;
		switch ($other->rounding_behaviour) {
			case(self::CEIL):
				$precision = pow(10, $other->rounding_precision);
				$changed = ceil($changed * $precision);
				$changed /= $precision;
				break;
			case(self::ROUND):
				$changed = round($changed, $other->rounding_precision);
				break;
			case(self::FLOOR):
				$precision = pow(10, $other->rounding_precision);
				$changed = floor($changed * $precision);
				$changed /= $precision;
				break;
		}
		return floatval($changed);
	}
}
