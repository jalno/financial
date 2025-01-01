<?php

namespace packages\financial\listeners;

use packages\financial\Contracts\IPaymentMethodEvent;
use packages\financial\PaymentMethdos\BankTransferPaymentMethod;
use packages\financial\PaymentMethdos\CreditPaymentMethod;
use packages\financial\PaymentMethdos\OnlinePaymentMethod;

class PaymentMethodsListener
{
    public function handle(IPaymentMethodEvent $event)
    {
        $event->add(CreditPaymentMethod::getInstance());
        $event->add(BankTransferPaymentMethod::getInstance());
        $event->add(OnlinePaymentMethod::getInstance());
    }
}
