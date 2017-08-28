<?php
namespace packages\financial\controllers\userpanel;
use \packages\userpanel\user;
use \packages\financial\currency;
use \packages\financial\controller;
use \packages\base\inputValidation;
class settings extends controller{
	protected $authentication = true;
	public function store(array $inputs, user $user){
		if(isset($inputs['financial_transaction_currency'])){
			if(!$currency = $user->option('financial_transaction_currency')){
				$currency = currency::getDefault();
			}
			if(!$currency instanceof dbObject){
				$currency = currency::byId($currency);
			}
			if($user->credit > 0 and $currency->id != $inputs['financial_transaction_currency']){
				$rate = new currency\rate();
				$rate->where('currency', $currency->id);
				$rate->where('changeTo', $inputs['financial_transaction_currency']);
				if(!$rate = $rate->getOne()){
					throw new inputValidation('financial_transaction_currency');
				}
				$user->credit *= $rate->price;
				$user->save();
			}

			$user->setOption('financial_transaction_currency', $inputs['financial_transaction_currency']);
		}
	}
}
