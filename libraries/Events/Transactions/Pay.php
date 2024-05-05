<?php

namespace packages\financial\Events\Transactions;

use packages\base\Event;
use packages\financial\Transaction;
use packages\notifications\Notifiable;
use packages\userpanel\User;

class Pay extends Event implements Notifiable
{
    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public static function getName(): string
    {
        return 'financial_transaction_pay';
    }

    public static function getParameters(): array
    {
        return [Transaction::class];
    }

    public function getArguments(): array
    {
        return [
            'transaction' => $this->getTransaction(),
        ];
    }

    public function getTargetUsers(): array
    {
        $parents = $this->transaction->user->parentTypes();
        $users = [];
        if ($parents) {
            $user = new User();
            $user->where('type', $parents, 'in');
            foreach ($user->get() as $user) {
                $users[$user->id] = $user;
            }
        }

        return $users;
    }
}
