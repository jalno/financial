<?php
namespace themes\clipone\views\financial\settings\gateways;
use \packages\base\translator;
use \packages\base\options;
use \packages\financial\payport as gateway;
use \packages\financial\views\settings\gateways\edit as editView;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\formTrait;
class edit extends editView{
	use viewTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(translator::trans("settings.financial.gateways.edit"));
		$this->setNavigation();
		$this->addBodyClass('transaction-settings-gateway');
	}
	private function setNavigation(){
		navigation::active("settings/financial/gateways");
	}
	public function getGatewaysForSelect(){
		$options = array();
		foreach($this->getGateways() as $gateway){
			$title = translator::trans('financial.gateway.'.$gateway->getName());
			$options[] = array(
				'value' => $gateway->getName(),
				'title' => $title ? $title : $gateway->getName()
			);
		}
		return $options;
	}
	public function getGatewayStatusForSelect(){
		$options = array(
			array(
				'title' => translator::trans('financial.gateway.status.active'),
				'value' => gateway::active
			),
			array(
				'title' => translator::trans('financial.gateway.status.deactive'),
				'value' => gateway::deactive
			)
		);
		return $options;
	}
	protected function getNumbersData(){
		$numbers = array();
		foreach($this->getGateway()->numbers as $number){
			$numberData = $number->toArray();
			if(options::get('packages.financial.defaultNumber') == $number->id){
				$numberData['primary'] = true;
			}
			$numbers[] = $numberData;
		}
		return $numbers;
	}
	protected function getCurrenciesForSelect():array{
		$currencies = [];
		foreach($this->getCurrencies() as $currency){
			$currencies[] = [
				'label' => $currency->title,
				'value' => $currency->id
			];
		}
		return $currencies;
	}
	protected function getAccountsForSelect(): array {
		$accounts = array();
		foreach ($this->getAccounts() as $account) {
			$accounts[] = array(
				"title" => $account->title . " - " . $account->shaba,
				"value" => $account->id,
			);
		}
		return $accounts;
	}
}
