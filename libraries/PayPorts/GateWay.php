<?php
namespace packages\financial\PayPort;
use packages\base;
use packages\financial\PayPort;
use packages\financial\PayPortPay;

abstract class GateWay{
	abstract public function __construct(PayPort $payport);
	abstract public function PaymentRequest(PayPortPay $pay);
	abstract public function PaymentVerification(PayPortPay $pay);
	protected function callbackURL(PayPortPay $pay){
		$query = array(
			'token' => $pay->transaction->token
		);
		return base\url("transactions/pay/onlinepay/callback/".$pay->id, $query, true);
	}
}
class Redirect{
	const get = 'get';
	const post = 'post';
	public $method;
	public $url;
	public $data = array();
	public function getURL(){
		return $this->url.(($this->method == self::get and $this->data) ? '?'.http_build_query($this->data) : '');
	}
	public function addData($key, $value){
		$this->data[$key] = $value;
	}
}


