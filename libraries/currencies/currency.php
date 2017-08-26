<?php
namespace packages\financial;
use \packages\base\db\dbObject;
use \packages\userpanel\date;
class currency extends dbObject{
	use Paramable;
	protected $dbTable = "financial_currencies";
	protected $primaryKey = "id";
	protected $dbFields = [
        'title' => ['type' => 'text', 'required' => true]
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
	public function hasRate():bool{
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		return $rate->has();
	}
	public function getCountRates():int{
		$rate = new currency\rate();
		$rate->where('currency', $this->id);
		return $rate->count();
	}
}
