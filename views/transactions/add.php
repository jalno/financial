<?php
namespace packages\financial\views\transactions;
class add extends \packages\financial\views\form{
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
}
