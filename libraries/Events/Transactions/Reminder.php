<?php

namespace packages\financial\Events\Transactions;

use packages\base\Event;
use packages\financial\Transaction;
use packages\notifications\Notifiable;

class Reminder extends Event implements Notifiable
{
    private $transaction;

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
        return 'financial_transaction_reminder';
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
        $users = [$this->transaction->user];

        return $users;
    }
}
