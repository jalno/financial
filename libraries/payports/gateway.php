<?php
namespace packages\financial\payport;
use packages\userpanel;
use packages\financial\payport_pay;

abstract class gateway{
	abstract public function PaymentRequest(payport_pay $pay);
	abstract public function PaymentVerification(payport_pay $pay);
	protected function callbackURL(payport_pay $pay){
		return userpanel\url("transactions/pay/onlinepay/callback/".$pay->id, array(), true);
	}
}
class redirect{
	const get = 'get';
	const post = 'post';
	public $method;
	public $url;
	public $data = array();
	public function getURL(){
		return $this->url.($this->data ? '?'.http_build_query($this->data) : '');
	}
}
class GatewayException extends \Exception{}
class VerificationException extends \Exception{}
class AlreadyVerified extends VerificationException{
	protected $message = 'alreadyverified';
}
