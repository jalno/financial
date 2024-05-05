<?php

namespace packages\financial\Logs\Transactions;

use packages\base\View;
use packages\userpanel\Logs;

class Add extends Logs
{
    public function getColor(): string
    {
        return 'circle-green';
    }

    public function getIcon(): string
    {
        return 'fa fa-money';
    }

    public function buildFrontend(View $view)
    {
    }
}
