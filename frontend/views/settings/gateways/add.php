<?php
namespace themes\clipone\views\financial\settings\gateways;
use \packages\base\translator;
use \packages\base\events;
use \packages\base\frontend\theme;

use \packages\userpanel;
use \packages\financial\payport as gateway;
use \packages\financial\views\settings\gateways\add as addView;

use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\breadcrumb;
use \themes\clipone\views\formTrait;

class add extends addView{
	use viewTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(translator::trans("settings.financial.gateways.add"));
		$this->setNavigation();
		$this->addBodyClass('transaction-settings-gateway');
	}
	public function addAssets(){
		$this->addJSFile(theme::url('assets/plugins/jquery-validation/dist/jquery.validate.min.js'));
		$this->addJSFile(theme::url('assets/plugins/bootstrap-inputmsg/bootstrap-inputmsg.min.js'));
		$this->addJSFile(theme::url('assets/js/pages/GateWays.js'));
	}
	private function setNavigation(){
		navigation::active("settings/financial/gateways");
	}
	public function getGatewaysForSelect(){
		$options = array();
		foreach($this->getGateways()->get() as $gateway){
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
