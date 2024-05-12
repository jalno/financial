<?php

namespace themes\clipone\Listeners\Financial;

use packages\financial\Authorization;
use themes\clipone\Events\InitializeProfile as Event;
use themes\clipone\Views\Financial\Profile\StatsBox;

class InitializeProfile
{
    public function handle(Event $e)
    {
        if (Authorization::is_accessed('paid_user_profile')) {
            $e->view->addBox(new StatsBox($e->view->getData('user')));
        }
    }
}
