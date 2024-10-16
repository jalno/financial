<?php
namespace themes\clipone\views\financial\settings\gateways;

use packages\base;
use packages\base\{Events, frontend\Theme, Translator};
use packages\userpanel;
use packages\financial\Payport as gateway;
use themes\clipone\{Breadcrumb, views\FormTrait, Navigation, ViewTrait};
use packages\financial\views\settings\gateways\Add as AddView;

class Add extends AddView {
	use ViewTrait, FormTrait;
	function __beforeLoad(){
		$this->setTitle(t("settings.financial.gateways.add"));
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
			$title = t('financial.gateway.'.$gateway->getName());
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
				'title' => t('financial.gateway.status.active'),
				'value' => gateway::active
			),
			array(
				'title' => t('financial.gateway.status.deactive'),
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
				"title" => t("select.none"),
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
