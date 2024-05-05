<?php
namespace packages\financial\Controllers\Settings;
use \packages\userpanel;
use \packages\userpanel\{User, Date};
use \packages\base\{NotFound, HTTP, DB, DB\Parenthesis, DB\DuplicateRecord, Views\FormError, View\Error, InputValidation, Events, Options};
use packages\financial\{API, View, Currency, Authentication, Controller, Authorization, PayPort as GateWay, Events\GateWays as GateWaysEvent, Bank\Account as BankAccount,Views\Settings\GateWays\Search,Views\Settings\GateWays\Add,Views\Settings\GateWays\Delete,Views\Settings\GateWays\Edit};
class GateWays extends Controller{
	protected $authentication = true;
	public function listgateways(){
		Authorization::haveOrFail('settings_gateways_search');
		$view = View::byName(Search::class);
		$gateways = new GatewaysEvent();
		Events::trigger($gateways);
		$gateway = new GateWay();
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
				if(!in_array($inputs['status'], array(GateWay::active, GateWay::deactive))){
					throw new InputValidation("status");
				}
			}
			if(isset($inputs['gateway']) and $inputs['gateway']){
				if(!in_array($inputs['gateway'], $gateways->getGatewayNames())){
					throw new InputValidation("gateway");
				}
			}
			if (isset($inputs["account"]) and $inputs["account"]) {
				$bankaccount = new BankAccount();
				$bankaccount->where("status", BankAccount::Active);
				$bankaccount->where("id", $inputs["account"]);
				if (!$bankaccount->has()) {
					throw new InputValidation("account");
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
				$parenthesis = new Parenthesis();
				foreach(array('title') as $item){
					if(!isset($inputs[$item]) or !$inputs[$item]){
						$parenthesis->where("financial_gateways.".$item,$inputs['word'], $inputs['comparison'], 'OR');
					}
				}
				$gateway->where($parenthesis);
			}
		}catch(InputValidation $error){
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
		Authorization::haveOrFail('settings_gateways_add');
		$view = view::byName(Add::class);
		$gateways = new GateWaysEvent();
		Events::trigger($gateways);
		$view->setGateways($gateways);
		$view->setCurrencies(Currency::get());
		if(HTTP::is_post()){
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
					'values' => array(GateWay::active, GateWay::deactive)
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
						$bankaccount = new BankAccount();
						$bankaccount->where("status", BankAccount::Active);
						$bankaccount->where("id", $inputs["account"]);
						if (!$bankaccount->has()) {
							throw new InputValidation("account");
						}
					} else {
						unset($inputs["account"]);
					}
				}
				if(isset($inputs['currency'])){
					if($inputs['currency']){
						if(!is_array($inputs['currency'])){
							throw new InputValidation('currency');
						}
					}else{
						unset($inputs['currency']);
					}
				}
				if(isset($inputs['currency'])){
					foreach($inputs['currency'] as $key => $currency){
						if(!Currency::byId($currency)){
							throw new InputValidation("currency[{$key}]");
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
				$gatewayObj = new GateWay();
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
			}catch(InputValidation $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}catch(DuplicateRecord $error){
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
		Authorization::haveOrFail('settings_gateways_delete');
		$gateway = (new GateWay)->byID($data['gateway']);
		if (!$gateway) {
			throw new NotFound;
		}
		$view = View::byName(Delete::class);
		$view->setGateway($gateway);
		if(HTTP::is_post()){
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
		Authorization::haveOrFail('settings_gateways_edit');
		$gatewayObj = (new GateWay)->byID($data['gateway']);
		if (!$gatewayObj) {
			throw new NotFound;
		}
		$view = View::byName(Edit::class);
		$gateways = new GateWaysEvent();
		Events::trigger($gateways);
		$view->setGateways($gateways->get());
		$view->setGateway($gatewayObj);
		$view->setCurrencies(Currency::get());
		if (HTTP::is_post()) {
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
					'values' => array(GateWay::active, GateWay::deactive),
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
						$bankaccount = new BankAccount();
						$bankaccount->where("status", BankAccount::Active);
						$bankaccount->where("id", $inputs["account"]);
						if (!$bankaccount->has()) {
							throw new InputValidation("account");
						}
					} else {
						unset($inputs["gateway"]);
					}
				}
				if(isset($inputs['currency'])){
					if($inputs['currency']){
						if(!is_array($inputs['currency'])){
							throw new InputValidation("currency");
						}
					}else{
						unset($inputs['currency']);
					}
				}
				if(isset($inputs['currency'])){
					foreach($inputs['currency'] as $key => $currency){
						if(!Currency::byId($currency)){
							throw new InputValidation("currency[{$key}]");
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
			}catch(InputValidation $error){
				$view->setFormError(FormError::fromException($error));
				$this->response->setStatus(false);
			}catch(DuplicateRecord $error){
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
