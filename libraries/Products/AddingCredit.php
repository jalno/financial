<?php

namespace packages\financial\Products;

use packages\financial\TransactionProduct;
use packages\userpanel\User;

class AddingCredit extends TransactionProduct
{
    public function trigger_paid()
    {
        $user = new User();
        $user->where('id', $this->transaction->user->id);
        $user = $user->getOne();
        $user->credit += $this->price;
        $user->save();
    }
}
