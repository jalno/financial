<?php

namespace packages\financial\Listeners;

use packages\financial\Events as FinancialEevents;
use packages\notifications\Events;

class Notifications
{
    public function events(Events $events)
    {
        $events->add(FinancialEevents\Transactions\Add::class);
        $events->add(FinancialEevents\Transactions\Edit::class);
        $events->add(FinancialEevents\Transactions\Expire::class);
        $events->add(FinancialEevents\Transactions\Pay::class);
        $events->add(FinancialEevents\Transactions\Reminder::class);
        $events->add(FinancialEevents\Transactions\Refund\Accepted::class);
        $events->add(FinancialEevents\Transactions\Refund\Rejected::class);
    }
}
