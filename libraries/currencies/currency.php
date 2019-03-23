<?php
namespace packages\financial;

use packages\base\{options, db, db\dbObject};
use packages\userpanel\user;

class currency extends dbObject{
	use Paramable;
	protected $dbTable = "financial_currencies";
	protected $primaryKey = "id";
	protected $dbFields = [
        'title' => ['type' => 'text', 'required' => true],
        'update_at' => ['type' => 'int', 'required' => true]
    ];
	protected $relations = [
		'params' => ['hasMany', 'packages\\financial\\currency\\param', 'currency'],
		'rates' => ['hasMany', 'packages\\financial\\currency\\rate', 'currency']
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
	public function changeTo(float $price, currency $other): float {
		if ($other->id == $this->id) {
			return $price;
		}
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		$rate->where('changeTo', $other->id);
		if(!$rate = $rate->getOne()){
			throw new currency\UnChangableException($other, $this);
		}
		return $price * $rate->price;
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
