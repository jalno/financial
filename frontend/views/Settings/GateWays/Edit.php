<?php
namespace themes\clipone\views\financial\Settings\GateWays;
use \packages\base\Translator;
use \packages\base\Options;
use \packages\financial\PayPort as GateWay;
use \packages\financial\Views\Settings\GateWays\Edit as EditView;
use \themes\clipone\ViewTrait;
use \themes\clipone\Navigation;
use \themes\clipone\Views\FormTrait;
class Edit extends EditView{
	use ViewTrait, FormTrait;
	function __beforeLoad(){
		$this->setTitle(Translator::trans("settings.financial.gateways.edit"));
		$this->setNavigation();
		$this->addBodyClass('transaction-settings-gateway');
	}
	private function setNavigation(){
		Navigation::active("settings/financial/gateways");
	}
	public function getGatewaysForSelect(){
		$options = array();
		foreach($this->getGateways() as $gateway){
			$title = Translator::trans('financial.gateway.'.$gateway->getName());
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
				'title' => Translator::trans('financial.gateway.status.active'),
				'value' => GateWay::active
			),
			array(
				'title' => Translator::trans('financial.gateway.status.deactive'),
				'value' => GateWay::deactive
			)
		);
		return $options;
	}
	protected function getNumbersData(){
		$numbers = array();
		foreach($this->getGateway()->numbers as $number){
			$numberData = $number->toArray();
			if(Options::get('packages.financial.defaultNumber') == $number->id){
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
		$accounts = array(
			array(
				"title" => "هیچ کدام",
				"value" => "",
			),
		);
		foreach ($this->getAccounts() as $account) {
			$accounts[] = array(
				"title" => $account->title . " - " . $account->shaba,
				"value" => $account->id,
			);
		}
		return $accounts;
	}
}
