<?php
namespace packages\userpanel\controllers;
use \packages\base;
use \packages\base\http;
use \packages\base\inputValidation;
use \packages\base\db\duplicateRecord;
use \packages\base\db\InputDataType;
use \packages\base\views\FormError;
use \packages\userpanel;
use \packages\userpanel\transactions as lib_transactions;
use \packages\userpanel\authorization;
use \packages\userpanel\authentication;
use \packages\userpanel\controller;
use \packages\userpanel\view;
use \packages\userpanel\log;

class transactions extends controller{
	protected $authentication = true;
	public function index(){
		//if(authorization::is_accessed('transaction_list')){
			echo("chera rafti!!!");
			$transaction = new lib_transactions();
			$transaction->pageLimit = $this->items_per_page;
			$transactions = $transaction->paginate($this->page);
			$this->total_pages = $transaction->totalPages;

			if($view = view::byName("\\packages\\userpanel\\views\\transactions\\listview")){
				$view->setDataList($transactions);
				$view->setPaginate($this->page, $this->total_pages, $this->items_per_page);
				$this->response->setStatus(true);
				$this->response->setView($view);
				return $this->response;
			}else{
				throw new \Exception('view');

			}
		//}else{
		//	echo("cheraaaa!!!");
		//	return authorization::FailResponse();
		//}
	}
	public function add($data){
		if(authorization::is_accessed('transactions_add')){
			if($view = view::byName("\\packages\\userpanel\\views\\transactions\\add")){
				if(http::is_post()){
					$inputs = array(
						'title' => array(
							'type' => 'string'
						),
						'price' => array(
							'type' => 'number'
						),
						'cliente' => array(
							'type' => 'email'
						),
						'datecreatetion' => array(
							'type' => 'string'
						),
						'datexpiretion' => array(
							'type' => 'string'
						),
						'product' => array(
							'type' => 'string'
						),
						'description' => array(
							'type' => 'string'
						)
					);
					$this->response->setStatus(false);
					try{
						$formdata = $this->checkinputs($inputs);
						$transaction = new transaction($formdata);
						if($transaction->save()){
							$log = new log();
							$log->type = log::transaction_edit;
							$log->transactions = array_unique(array($transaction->id, authentication::getID()));
							$log->params = array(
								'transaction' => $transaction->id,
								'inputs' => $formdata
							);
							$log->save();
							$this->response->setStatus(true);
							$this->response->go(userpanel\url('transactions/edit/'.$transaction->id));
						}
					}catch(inputValidation $error){
						$view->setFormError(FormError::fromException($error));
					}catch(InputDataType $error){
						$view->setFormError(FormError::fromException($error));
					}catch(duplicateRecord $error){
						$view->setFormError(FormError::fromException($error));
					}
					$view->setDataForm($this->inputsvalue($inputs));
				}else{
					$this->response->setStatus(true);
				}
				$this->response->setView($view);
				return $this->response;
			}
		}else{
			return authorization::FailResponse();
		}
	}
	public function view($data){
		if(authorization::is_accessed('transactions_view')){
			$transaction = transaction::with('type')->with('socialnetworks')->byId($data['transaction']);
			if($view = view::byName("\\packages\\userpanel\\views\\transactions\\view")){
				$view->setData($transaction, 'transaction');
				$this->response->setStatus(true);
				$this->response->setView($view);
				return $this->response;
			}
		}else{
			return authorization::FailResponse();
		}
	}
	public function edit($data){
		if(authorization::is_accessed('transactions_edit')){
			$transaction = transaction::byId($data['transaction']);

			if($view = view::byName("\\packages\\userpanel\\views\\transactions\\edit")){
				if(http::is_post()){
					$inputs = array(
						'name' => array(
							'optional' => true,
							'type' => 'string'
						),
						'email' => array(
							'type' => 'email',
							'optional' => true,
						),
						'cellphone' => array(
							'type' => 'cellphone',
							'optional' => true,
						),
						'type' => array(
							'optional' => true,
							'type' => 'number'
						),
						'zipcode' => array(
							'optional' => true,
							'type' => 'number'
						),
						'city' => array(
							'optional' => true,
							'type' => 'string'
						),
						'country' => array(
							'optional' => true,
							'type' => 'string'
						),
						'address' => array(
							'optional' => true,
							'type' => 'string'
						),
						'status' => array(
							'optional' => true,
							'type' => 'number',
							'values' => array(0,1,2)
						)
					);
					$this->response->setStatus(false);
					try{
						$formdata = $this->checkinputs($inputs);
						$transaction->save($formdata);
						$log = new log();
						$log->type = log::transaction_edit;
						$log->transactions = array_unique(array($transaction->id, authentication::getID()));
						$log->params = array(
							'transaction' => $transaction->id,
							'inputs' => $formdata
						);
						$log->save();
						$this->response->setStatus(true);
						$view->setDataForm($transaction->toArray());
					}catch(inputValidation $error){
						$view->setFormError(FormError::fromException($error));
						$view->setDataForm($this->inputsvalue($inputs));
					}catch(InputDataType $error){
						$view->setFormError(FormError::fromException($error));
						$view->setDataForm($this->inputsvalue($inputs));
					}catch(duplicateRecord $error){
						$view->setFormError(FormError::fromException($error));
						$view->setDataForm($this->inputsvalue($inputs));
					}

				}else{
					$this->response->setStatus(true);
				}
				$view->setDataForm($transaction->toArray());
				$this->response->setView($view);
				return $this->response;
			}
		}else{
			return authorization::FailResponse();
		}
	}
	public function delete($data){
		if(authorization::is_accessed('transactions_delete')){
			$transaction = transaction::byId($data['transaction']);

			if($view = view::byName("\\packages\\userpanel\\views\\transactions\\delete")){
				if(http::is_post()){
					$transaction->delete();
					$log = new log();
					$log->type = log::transaction_delete;
					$log->transactions = array_unique(array($transaction->id, authentication::getID()));
					$log->params = array(
						'transaction' => $transaction->id,
					);
					$log->save();
					$this->response->setStatus(true);
					$this->response->go(userpanel\url('transactions'));
				}else{
					$this->response->setStatus(true);
					$view->setDataForm($transaction->toArray());
					$this->response->setView($view);
				}
				return $this->response;
			}
		}else{
			return authorization::FailResponse();
		}
	}
}
