<?php

namespace themes\clipone\listeners\financial;

use packages\financial\Authorization;
use themes\clipone\Events\InitializeProfile as Event;
use themes\clipone\views\financial\Profile\StatsBox;

class InitializeProfile
{
    public function handle(Event $e)
    {
        if (Authorization::is_accessed('paid_user_profile')) {
            $e->view->addBox(new StatsBox($e->view->getData('user')));
        }
    }
}
