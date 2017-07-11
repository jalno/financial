<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\form;
class addingcredit extends form{
	public function setClient(int $client){
		$this->setData($client, 'client');
		$this->setDataForm($client, 'client');
	}
	protected function getClient():int{
		return $this->getData('client');
	}
}
