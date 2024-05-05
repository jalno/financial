<?php
namespace packages\financial\Views\Transactions;
class Add extends \packages\financial\Views\Form{
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
}
