<?php
namespace packages\financial\Views\Transactions;
use \packages\financial\Views\Form;
class AddingCredit extends Form{
	public function setClient(int $client){
		$this->setData($client, 'client');
		$this->setDataForm($client, 'client');
	}
	protected function getClient():int{
		return $this->getData('client');
	}
}
