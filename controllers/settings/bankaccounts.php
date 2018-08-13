<?php
namespace packages\financial\controllers\settings;
use packages\userpanel;
use packages\base\{http, NotFound, views\FormError, inputValidation, view\error};
use packages\financial\{view, usertype, controller, authorization, authentication, bankaccount, payport};

/**
  * Handler for usertypes
  * @author Mahdi Abedi <abedi@jeyserver.com>
  * @copyright 2016 JeyServer
  */
class bankaccounts extends controller{
	/**
	* @var bool require authentication
	*/
	protected $authentication = true;

	/**
	* Search and listing for usertypes
	* @throws inputValidation for input validation
	* @throws inputValidation if id value is not in childrenTypes
	* @return \packages\base\response
	*/
	public function listAcoounts(){
		authorization::haveOrFail('settings_bankaccounts_list');
		$view = view::byName("\\packages\\financial\\views\\settings\\bankaccount\\listview");
		$types = authorization::childrenTypes();

		$bankaccount = new bankaccount();

		$inputsRules = array(
			'id' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true,
			),
			'title' => array(
				'type' => 'string',
				'optional' => true,
				'empty' => true,
			),
			"shaba" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
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
		try{
			$inputs = $this->checkinputs($inputsRules);

			//checking id for being on children types
			if(isset($inputs['id']) and $inputs['id']){
				if(!in_array($inputs['id'], $types)){
					throw new inputValidation("id");
				}
			}

			//notmal search
			foreach(array('id', 'bank', "shaba") as $item){
				if(isset($inputs[$item]) and $inputs[$item]){
					$comparison = $inputs['comparison'];
					if(in_array($item, array('id'))){
						$comparison = 'equals';
					}
					$bankaccount->where($item, $inputs[$item], $comparison);
				}
			}
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
		}

		//refill the search form
		$view->setDataForm($this->inputsvalue($inputsRules));

		//query with respect for pagination process
		//$userpanel_settings_usertypes_edit->pageLimit = $this->items_per_page;
		$bankaccounts = $bankaccount->paginate($this->page);
		$this->total_pages = $bankaccount->totalPages;
		$view->setDataList($bankaccounts);
		$view->setPaginate($this->page, $bankaccount->totalCount, $this->items_per_page);

		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function delete($data){
		authorization::haveOrFail('settings_bankaccounts_delete');
		$view = view::byName("\\packages\\financial\\views\\settings\\bankaccount\\delete");
		$bankaccount = bankaccount::byId($data['id']);
		if(!$bankaccount){
			throw new NotFound;
		}
		$view->setBankaccount($bankaccount);
		$this->response->setStatus(false);
		if(http::is_post()){
			try {
				$payport = new payport();
				$payport->where("account", $bankaccount->id);
				$payport->where("status", payport::active);
				if ($payport->has()) {
					throw new payportDependencies();
				}
				$bankaccount->delete();
				$this->response->setStatus(true);
				$this->response->GO(userpanel\url("settings/bankaccounts"));
			} catch (inputValidation $error) {
				$view->setFormError(FormError::fromException($error));
			} catch (payportDependencies $error) {
				$error = new error();
				$error->setType(error::FATAL);
				$error->setCode("financial.settings.bankaccount.gatewayDependencies");
				$view->addError($error);
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function edit($data){
		authorization::haveOrFail('settings_bankaccounts_edit');
		$view = view::byName("\\packages\\financial\\views\\settings\\bankaccount\\edit");
		$bankaccount = bankaccount::byId($data['id']);
		if(!$bankaccount){
			throw new NotFound;
		}
		$view->setBankaccount($bankaccount);
		$inputsRules = array(
			'title' => array(
				'type' => 'string',
				'optional' => true
			),
			'account' => array(
				'type' => 'number',
				'optional' => true
			),
			'cart' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true
			),
			"shaba" => array(
				"type" => "string",
				"optional" => true
			),
			'owner' => array(
				'type' => 'string',
				'optional' => true
			),
			'status' => array(
				'type' => 'number',
				'optional' => true,
				'values' => array(bankaccount::active, bankaccount::deactive)
			)
		);
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				if (isset($inputs["shaba"])) {
					if (!preg_match("/^IR\d{24}$/", $inputs["shaba"])) {
						throw new inputValidation("shaba");
					}
				}
				if(isset($inputs['title'])){
					$bankaccount->title = $inputs['title'];
				}
				if(isset($inputs['account'])){
					$bankaccount->account = $inputs['account'];
				}
				if(isset($inputs['cart']) and $inputs['cart']){
					$bankaccount->cart = $inputs['cart'];
				}
				if(isset($inputs["shaba"])){
					$bankaccount->shaba = $inputs["shaba"];
				}
				if(isset($inputs['owner'])){
					$bankaccount->owner = $inputs['owner'];
				}
				if(isset($inputs['status'])){
					if ($inputs["status"] == bankaccount::deactive) {
						$payport = new payport();
						$payport->where("account", $bankaccount->id);
						$payport->where("status", payport::active);
						if ($payport->has()) {
							throw new payportDependencies();
						}
					}
					$bankaccount->status = $inputs['status'];
				}
				$bankaccount->save();
				$this->response->setStatus(true);
				$this->response->GO(userpanel\url("settings/financial/bankaccounts/edit/".$bankaccount->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			} catch (payportDependencies $error) {
				$error = new error();
				$error->setType(error::FATAL);
				$error->setCode("financial.settings.bankaccount.gatewayDependencies");
				$view->addError($error);
			}
			$view->setDataForm($this->inputsvalue($inputsRules));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function add(){
		authorization::haveOrFail('settings_bankaccounts_add');
		$view = view::byName("\\packages\\financial\\views\\settings\\bankaccount\\add");
		$inputsRules = array(
			'title' => array(
				'type' => 'string',
			),
			'account' => array(
				'type' => 'number',
			),
			'cart' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true
			),
			"shaba" => array(
				"type" => "string",
			),
			'owner' => array(
				'type' => 'string',
			),
			'status' => array(
				'type' => 'number',
				'values' => array(bankaccount::active, bankaccount::deactive)
			)
		);
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				if (!preg_match("/^IR\d{24}$/", $inputs["shaba"])) {
					throw new inputValidation("shaba");
				}
				$bankaccount = new bankaccount();
				$bankaccount->title = $inputs['title'];
				$bankaccount->account = $inputs['account'];
				if(isset($inputs['cart']) and $inputs['cart']){
					$bankaccount->cart = $inputs['cart'];
				}
				$bankaccount->shaba = $inputs["shaba"];
				$bankaccount->owner = $inputs['owner'];
				$bankaccount->status = $inputs['status'];
				$bankaccount->save();
				$this->response->setStatus(true);
				$this->response->GO(userpanel\url("settings/financial/bankaccounts/edit/".$bankaccount->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
			$view->setDataForm($this->inputsvalue($inputsRules));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
}

class payportDependencies extends \Exception {}
