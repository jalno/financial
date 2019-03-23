<?php
namespace packages\financial\views\settings\gateways;
use packages\userpanel\views\form;
use packages\financial\{events\gateways, Bank};

class add extends form{
	public function setGateways(gateways $gateways){
		$this->setData($gateways, "gateways");
	}
	protected function getGateways(){
		return $this->getData('gateways');
	}
	public function setCurrencies(array $currencies){
		$this->setData($currencies, 'currencies');
	}
	protected function getCurrencies():array{
		return $this->getData('currencies');
	}
	protected function getAccounts(): array {
		$account = new Bank\Account();
		$account->where("status", Bank\Account::Active);
		return $account->get();
	}
}
