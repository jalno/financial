<?php

namespace packages\financial\products;

use packages\base\DB;
use packages\financial\transaction_product;

/**
 * @property \packages\financial\Transaction $transaction
 * @property float $price
 */
class addingcredit extends transaction_product
{
	public function trigger_paid()
	{
		DB::where('id', $this->transaction->user->id)
			->update('userpanel_users', [
				'credit' => DB::inc($this->price),
			]);
	}
}
