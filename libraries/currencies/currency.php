<?php
namespace packages\financial;

use packages\base\{options, db, db\dbObject};
use packages\financial\Currency\{Param, Rate};
use packages\userpanel\user;

class Currency extends dbObject {
	use Paramable;

	/** rounding-behaviour */
	const CEIL = 1;
	const ROUND = 2;
	const FLOOR = 3;

	protected $dbTable = "financial_currencies";
	protected $primaryKey = "id";
	protected $dbFields = [
        'title' => ['type' => 'text', 'required' => true],
		'update_at' => ['type' => 'int', 'required' => true],
		'rounding_behaviour' => ['type' => 'int'],
		'rounding_precision' => ['type' => 'double'],
    ];
	protected $relations = [
		'params' => ['hasMany', Param::class, 'currency'],
		'rates' => ['hasMany', Rate::class, 'currency'],
	];
	public function addRate(currency $currency, float $price){
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		$rate->where('changeTo', $currency->id);
		if($rate = $rate->getOne()){
			$rate->price = $price;
			$rate->save();
		}else{
			$rate = new currency\rate();
			$rate->currency = $this->id;
			$rate->changeTo = $currency->id;
			$rate->price = $price;
			$rate->save();
		}
	}
	public function deleteRate(int $rate = 0){
		if($rate == 0){
			foreach($this->rates as $rate){
				$rate->delete();
			}
		}else{
			$rate = new currency\rate();
			if($rate = $rate->byId($rate)){
				$rate->delete();
			}
		}
	}
	public function hasRate(int $with = 0):bool{
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		if($with > 0){
			$rate->where('changeTo', $with);
		}
		return $rate->has();
	}
	public function getRate(int $changeTo) {
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		$rate->where('changeTo', $changeTo);
		return $rate->getOne();
	}
	public function changeTo(float $price, Currency $other): float {
		if ($other->id == $this->id) {
			return $price;
		}
		$rate = new Currency\Rate();
		$rate->where('currency', $this->id);
		$rate->where('changeTo', $other->id);
		$rate = $rate->getOne();
		if (!$rate) {
			throw new currency\UnChangableException($other, $this);
		}
		$changed = $price * $rate->price;
		switch ($other->behaviour) {
			case(self::CEIL): $changed = ceil($changed); break;
			case(self::ROUND): $changed = round($changed, $other->precision ?? 0); break;
			case(self::FLOOR): $changed = floor($changed); break;
		}
		return $changed;
	}
	public function getCountRates():int{
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		return $rate->count();
	}
	public static function getDefault(user $user = null):currency{
		$currency = null;
		if($user){
			$currency = $user->option('financial_transaction_currency');
		}
		if(!$currency){
			if(!$currency = options::get('packages.financial.defaultCurrency')){
				throw new currency\undefinedCurrencyException();
			}
			if($user){
				$user->option('financial_transaction_currency', $currency);
			}
		}
		$return  = new currency();
		$return->where('id', $currency);
		return $return->getOne();
	}
}
