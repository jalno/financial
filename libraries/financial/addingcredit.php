<?php
namespace packages\financial\products;
use \packages\userpanel\user;
use \packages\financial\transaction_product;
class addingcredit extends transaction_product{
	public function trigger_paid(){
		$user = new user;
		$user->where("id", $this->transaction->user->id);
		$user = $user->getOne();
		$user->credit = $this->transaction->price;
		$user->save();
	}
}
