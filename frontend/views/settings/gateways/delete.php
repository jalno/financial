<?php
namespace themes\clipone\views\financial\settings\gateways;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\views\settings\gateways\delete as deleteView;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;

class delete extends deleteView{
	use viewTrait;
	function __beforeLoad(){
		$this->setTitle(translator::trans("settings.financial.gateways.delete"));
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
}
