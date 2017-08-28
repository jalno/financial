<?php
namespace packages\financial\payport;
use packages\userpanel;
use packages\financial\payport;
use packages\financial\payport_pay;

abstract class gateway{
	abstract public function __construct(payport $payport);
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
		return $this->url.(($this->method == self::get and $this->data) ? '?'.http_build_query($this->data) : '');
	}
	public function addData($key, $value){
		$this->data[$key] = $value;
	}
}
class GatewayException extends \Exception{}
class VerificationException extends \Exception{}
class AlreadyVerified extends VerificationException{
	protected $message = 'alreadyverified';
}
class unSupportCurrencyTypeException extends \Exception{
	private $currency;
	public function __construct($currency, string $message = ''){
		$this->currency = $currency;
		parent::__construct($message);
	}
}