<?php
namespace packages\financial\controllers\settings;
use \packages\userpanel;
use \packages\userpanel\{user, date};
use \packages\base\{NotFound, http, db, db\parenthesis, db\duplicateRecord, views\FormError, view\error, inputValidation, events, options};
use packages\financial\{api, view, currency, authentication, controller, authorization, payport as gateway, events\gateways as gatewaysEvent, Bank\Account as bankaccount};

class gateways extends controller{
	protected $authentication = true;
	public function listgateways(){
		authorization::haveOrFail('settings_gateways_search');
		$view = view::byName("\\packages\\financial\\views\\settings\\gateways\\search");
		$gateways = new gatewaysEvent();
		events::trigger($gateways);
		$gateway = new gateway();
		$inputsRules = array(
			'id' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true
			),
			'title' => array(
				'type' => 'string',
				'optional' =>true,
				'empty' => true
			),
			'gateway' => array(
				'type' => 'string',
				'optional' =>true,
				'empty' => true
			),
			"account" => array(
				"type" => "string",
				"optional" =>true,
				"empty" => true
			),
			'status' => array(
				'type' => 'number',
				'optional' =>true,
				'empty' => true
			),
			'word' => array(
				'type' => 'string',
				'optional' => true,
				'empty' => true
			),
			'comparison' => array(
				'values' => array('equals', 'startswith', 'contains'),
				'default' => 'contains',
				'optional' => true
			)
		);
		$this->response->setStatus(true);
		try{
			$inputs = $this->checkinputs($inputsRules);
			if(isset($inputs['status']) and $inputs['status'] != 0){
				if(!in_array($inputs['status'], array(gateway::active, gateway::deactive))){
					throw new inputValidation("status");
				}
			}
			if(isset($inputs['gateway']) and $inputs['gateway']){
				if(!in_array($inputs['gateway'], $gateways->getGatewayNames())){
					throw new inputValidation("gateway");
				}
			}
			if (isset($inputs["account"]) and $inputs["account"]) {
				$bankaccount = new bankaccount();
				$bankaccount->where("status", bankaccount::Active);
				$bankaccount->where("id", $inputs["account"]);
				if (!$bankaccount->has()) {
					throw new inputValidation("account");
				}
			}

			foreach(array('id', 'title', 'gateway', "account", 'status') as $item){
				if(isset($inputs[$item]) and $inputs[$item]){
					$comparison = $inputs['comparison'];
					if(in_array($item, array('id','gateway', "account", 'status'))){
						$comparison = 'equals';
						if($item == 'gateway'){
							$inputs[$item] = $gateways->getByName($inputs[$item]);
						}
					}
					$gateway->where($item, $inputs[$item], $comparison);
				}
			}
			if(isset($inputs['word']) and $inputs['word']){
				$parenthesis = new parenthesis();
				foreach(array('title') as $item){
					if(!isset($inputs[$item]) or !$inputs[$item]){
						$parenthesis->where("financial_gateways.".$item,$inputs['word'], $inputs['comparison'], 'OR');
					}
				}
				$gateway->where($parenthesis);
			}
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
			$this->response->setStatus(false);
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$gateway->orderBy('id', 'ASC');
		$gateway->pageLimit = $this->items_per_page;
		$items = $gateway->paginate($this->page);
		$view->setPaginate($this->page, $gateway->totalCount, $this->items_per_page);
		$view->setDataList($items);
		$view->setGateways($gateways);
		$this->response->setView($view);
		return $this->response;
	}
	public function add(){
		authorization::haveOrFail('settings_gateways_add');
		$view = view::byName("\\packages\\financial\\views\\settings\\gateways\\add");
		$gateways = new gatewaysEvent();
		events::trigger($gateways);
		$view->setGateways($gateways);
		$view->setCurrencies(currency::get());
		if(http::is_post()){
			$inputsRules = array(
				'title' => array(
					'type' => 'string'
				),
				'gateway' => array(
					'type' => 'string',
					'values' => $gateways->getGatewayNames()
				),
				"account" => array(
					"type" => "number",
					"optional" => true,
					"empty" => true,
				),
				'status' => array(
					'type' => 'number',
					'values' => array(gateway::active, gateway::deactive)
				),
				'currency' => [
					'optional' => true
				]
			);
			$this->response->setStatus(true);
			try{
				$inputs = $this->checkinputs($inputsRules);
				if (isset($inputs["account"])) {
					if ($inputs["account"]) {
						$bankaccount = new bankaccount();
						$bankaccount->where("status", bankaccount::Active);
						$bankaccount->where("id", $inputs["account"]);
						if (!$bankaccount->has()) {
							throw new inputValidation("account");
						}
					} else {
						unset($inputs["account"]);
					}
				}
				if(isset($inputs['currency'])){
					if($inputs['currency']){
						if(!is_array($inputs['currency'])){
							throw new inputValidation('currency');
						}
					}else{
						unset($inputs['currency']);
					}
				}
				if(isset($inputs['currency'])){
					foreach($inputs['currency'] as $key => $currency){
						if(!currency::byId($currency)){
							throw new inputValidation("currency[{$key}]");
						}
					}
				}
				$gateway =  $gateways->getByName($inputs['gateway']);
				if($GRules = $gateway->getInputs()){
					$GRules = $inputsRules = array_merge($inputsRules, $GRules);
					$ginputs = $this->checkinputs($GRules);
				}
				if($GRules = $gateway->getInputs()){
					$gateway->callController($ginputs);
				}
				$gatewayObj = new gateway();
				$gatewayObj->title = $inputs['title'];
				if (isset($inputs["account"])) {
					$gatewayObj->account = $inputs["account"];
				}
				$gatewayObj->controller = $gateway->getHandler();
				$gatewayObj->status = $inputs['status'];
				foreach($gateway->getInputs() as $input){
					if(isset($ginputs[$input['name']])){
						$gatewayObj->setParam($input['name'],$ginputs[$input['name']]);
					}
				}
				$gatewayObj->save();
				if(isset($inputs['currency'])){
					foreach($inputs['currency'] as $currency){
						$gatewayObj->setCurrency($currency);
					}
				}
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('settings/financial/gateways/edit/'.$gatewayObj->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}catch(duplicateRecord $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}
			$view->setDataForm($this->inputsvalue($inputsRules));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function delete($data){
		authorization::haveOrFail('settings_gateways_delete');
		$gateway = (new Gateway)->byID($data['gateway']);
		if (!$gateway) {
			throw new NotFound;
		}
		$view = view::byName("\\packages\\financial\\views\\settings\\gateways\\delete");
		$view->setGateway($gateway);
		if(http::is_post()){
			$gateway->delete();

			$this->response->setStatus(true);
			$this->response->Go(userpanel\url('settings/financial/gateways'));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function edit($data){
		authorization::haveOrFail('settings_gateways_edit');
		$gatewayObj = (new gateway)->byID($data['gateway']);
		if (!$gatewayObj) {
			throw new NotFound;
		}
		$view = view::byName("\\packages\\financial\\views\\settings\\gateways\\edit");
		$gateways = new gatewaysEvent();
		events::trigger($gateways);
		$view->setGateways($gateways->get());
		$view->setGateway($gatewayObj);
		$view->setCurrencies(currency::get());
		if (http::is_post()) {
			$inputsRules = array(
				'title' => array(
					'type' => 'string',
					'optional' => true,
				),
				'gateway' => array(
					'type' => 'string',
					'values' => $gateways->getGatewayNames(),
					'optional' => true,
				),
				"account" => array(
					"type" => "number",
					'optional' => true,
					"empty" => true,
				),
				'status' => array(
					'type' => 'number',
					'values' => array(gateway::active, gateway::deactive),
					'optional' => true,
				),
				'currency' => [
					'optional' => true,
				]
			);
			$this->response->setStatus(true);
			try{
				$inputs = $this->checkinputs($inputsRules);
				if (isset($inputs["account"])) {
					if ($inputs["account"]) {
						$bankaccount = new bankaccount();
						$bankaccount->where("status", bankaccount::Active);
						$bankaccount->where("id", $inputs["account"]);
						if (!$bankaccount->has()) {
							throw new inputValidation("account");
						}
					} else {
						unset($inputs["gateway"]);
					}
				}
				if(isset($inputs['currency'])){
					if($inputs['currency']){
						if(!is_array($inputs['currency'])){
							throw new inputValidation("currency");
						}
					}else{
						unset($inputs['currency']);
					}
				}
				if(isset($inputs['currency'])){
					foreach($inputs['currency'] as $key => $currency){
						if(!currency::byId($currency)){
							throw new inputValidation("currency[{$key}]");
						}
					}
				}
				if (isset($inputs["gateway"])) {
					if ($inputs["gateway"]) {
						$gateway =  $gateways->getByName($inputs['gateway']);
						if($GRules = $gateway->getInputs()){
							$GRules = $inputsRules = array_merge($inputsRules, $GRules);
							$ginputs = $this->checkinputs($GRules);
						}
						if($GRules = $gateway->getInputs()){
							$gateway->callController($ginputs);
						}
					} else {
						unset($inputs["gateway"]);
					}
				}
				if (isset($inputs["title"])) {
					$gatewayObj->title = $inputs['title'];
				}
				if (isset($inputs["account"])) {
					$gatewayObj->account = $inputs["account"];
				}
				if (isset($inputs["gateway"])) {
					$gatewayObj->controller = $gateway->getHandler();
				}
				if (isset($inputs["status"])) {
					$gatewayObj->status = $inputs['status'];
				}
				if (isset($inputs["gateway"])) {
					foreach($gateway->getInputs() as $input){
						if(isset($ginputs[$input['name']])){
							$gatewayObj->setParam($input['name'],$ginputs[$input['name']]);
						}
					}
				}
				
				if(isset($inputs['currency'])){
					foreach($gatewayObj->getCurrencies() as $currency){
						if(($key = array_search($currency['currency'], $inputs['currency'])) !== false){
							unset($inputs['currency'][$key]);
						}else{
							$gatewayObj->deleteCurrency($currency['currency']);
						}
					}
					foreach($inputs['currency'] as $key => $currency){
						$gatewayObj->setCurrency($currency);
					}
				}
				$gatewayObj->save();
				$this->response->setStatus(true);
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}catch(duplicateRecord $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}
			$view->setDataForm($this->inputsvalue($inputsRules));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
}
