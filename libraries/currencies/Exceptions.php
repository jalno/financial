<?php
namespace packages\financial\currency;
class currencyException extends \Exception{}
class UnChangableException extends currencyException{
	private $currency;
	private $changeTo;
	public function __construct($currency, $changeTo, string $message = ''){
		$this->currency = $currency;
		$this->changeTo = $changeTo;
		parent::__construct($message);
	}
}
class undefinedCurrencyException extends currencyException{}