<?php
namespace packages\financial\views\settings\currencies;
use \packages\userpanel\views\form;
trait currenciesTrait{
	public function setCurrencies(array $currencies){
		$this->setData($currencies, "currencies");
	}
	protected function getCurrencies():array{
		return $this->getData("currencies");
	}
}
class add extends form{
	use currenciesTrait;
}
