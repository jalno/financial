<?php

namespace packages\financial\PayPort;

class UnSupportCurrencyTypeException extends \Exception
{
    private $currency;

    public function __construct($currency, string $message = '')
    {
        $this->currency = $currency;
        parent::__construct($message);
    }
}
