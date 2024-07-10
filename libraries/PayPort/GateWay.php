<?php

namespace packages\financial\PayPort;

use packages\base;
use packages\financial\PayPort;
use packages\financial\PayPortPay;

abstract class GateWay
{
    abstract public function __construct(PayPort $payport);

    abstract public function PaymentRequest(PayPortPay $pay);

    abstract public function PaymentVerification(PayPortPay $pay);

    protected function callbackURL(PayPortPay $pay)
    {
        $query = [
            'token' => $pay->transaction->token,
        ];

        return base\url('transactions/pay/onlinepay/callback/'.$pay->id, $query, true);
    }
}
class Redirect
{
    public const get = 'get';
    public const post = 'post';
    public $method;
    public $url;
    public $data = [];

    public function getURL()
    {
        return $this->url.((self::get == $this->method and $this->data) ? '?'.http_build_query($this->data) : '');
    }

    public function addData($key, $value)
    {
        $this->data[$key] = $value;
    }
}
