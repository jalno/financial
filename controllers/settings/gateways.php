<?php
namespace packages\financial\controllers\settings;
use \packages\base;
use \packages\base\frontend\theme;
use \packages\base\NotFound;
use \packages\base\http;
use \packages\base\db;
use \packages\base\db\parenthesis;
use \packages\base\db\duplicateRecord;
use \packages\base\views\FormError;
use \packages\base\view\error;
use \packages\base\inputValidation;
use \packages\base\events;
use \packages\base\options;

use \packages\userpanel;
use \packages\userpanel\user;
use \packages\userpanel\date;

use \packages\financial\view;
use \packages\financial\authentication;
use \packages\financial\controller;
use \packages\financial\authorization;
use \packages\financial\payport as gateway;
use \packages\financial\events\gateways as gatewaysEvent;

use \packages\financial\api;

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

			foreach(array('id', 'title', 'gateway', 'status') as $item){
				if(isset($inputs[$item]) and $inputs[$item]){
					$comparison = $inputs['comparison'];
					if(in_array($item, array('id','gateway', 'status'))){
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
		if(http::is_post()){
			$inputsRules = array(
				'title' => array(
					'type' => 'string'
				),
				'gateway' => array(
					'type' => 'string',
					'values' => $gateways->getGatewayNames()
				),
				'status' => array(
					'type' => 'number',
					'values' => array(gateway::active, gateway::deactive)
				)
			);
			$this->response->setStatus(true);
			try{
				$inputs = $this->checkinputs($inputsRules);
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
				$gatewayObj->controller = $gateway->getHandler();
				$gatewayObj->status = $inputs['status'];
				foreach($gateway->getInputs() as $input){
					if(isset($ginputs[$input['name']])){
						$gatewayObj->setParam($input['name'],$ginputs[$input['name']]);
					}
				}
				$gatewayObj->save();
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
		if(!$gateway = gateway::byID($data['gateway'])){
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
		if(!$gatewayObj = gateway::byID($data['gateway'])){
			throw new NotFound;
		}
		$view = view::byName("\\packages\\financial\\views\\settings\\gateways\\edit");
		$gateways = new gatewaysEvent();
		events::trigger($gateways);
		$view->setGateways($gateways->get());
		$view->setGateway($gatewayObj);
		if(http::is_post()){
			$inputsRules = array(
				'title' => array(
					'type' => 'string'
				),
				'gateway' => array(
					'type' => 'string',
					'values' => $gateways->getGatewayNames()
				),
				'status' => array(
					'type' => 'number',
					'values' => array(gateway::active, gateway::deactive)
				)
			);
			$this->response->setStatus(true);
			try{
				$inputs = $this->checkinputs($inputsRules);
				$gateway =  $gateways->getByName($inputs['gateway']);
				if($GRules = $gateway->getInputs()){
					$GRules = $inputsRules = array_merge($inputsRules, $GRules);
					$ginputs = $this->checkinputs($GRules);
				}
				if($GRules = $gateway->getInputs()){
					$gateway->callController($ginputs);
				}
				$gatewayObj->title = $inputs['title'];
				$gatewayObj->controller = $gateway->getHandler();
				$gatewayObj->status = $inputs['status'];
				foreach($gateway->getInputs() as $input){
					if(isset($ginputs[$input['name']])){
						$gatewayObj->setParam($input['name'],$ginputs[$input['name']]);
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
