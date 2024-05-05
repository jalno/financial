<?php
namespace packages\financial\Products;
use \packages\userpanel\User;
use \packages\financial\TransactionProduct;
class AddingCredit extends TransactionProduct{
	public function trigger_paid(){
		$user = new User;
		$user->where("id", $this->transaction->user->id);
		$user = $user->getOne();
		$user->credit += $this->price;
		$user->save();
	}
}
