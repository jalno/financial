<?php
namespace packages\financial\controllers;
use \packages\base;
use \packages\base\NotFound;
use \packages\base\http;
use \packages\base\db;
use \packages\base\views\inputValidation;
use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\authorization;
use \packages\financial\authentication;
use \packages\financial\controller;
use \packages\financial\view;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\financial\bankaccount;
use \packages\financial\payport;
use \packages\financial\payport_pay;
use \packages\financial\payport\redirect;
use \packages\financial\payport\GatewayException;
use \packages\financial\payport\VerificationException;
use \packages\financial\payport\AlreadyVerified;
class transactions extends controller{
	protected $authentication = true;
	function listtransactions(){
		if(authorization::is_accessed('transactions_list')){
			if($view = view::byName("\\packages\\financial\\views\\transactions\\listview")){
				$types = authorization::childrenTypes();
				db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
				if($types){
					db::where("userpanel_users.type", $types, 'in');
				}else{
					db::where("userpanel_users.id", authentication::getID());
				}
				db::orderBy('id', ' DESC');
				db::pageLimit($this->items_per_page);
				$transactionsData = db::paginate("financial_transactions", $this->page, array("financial_transactions.*"));
				$transactions = array();
				foreach($transactionsData as $transaction){
					$transactions[] = new transaction($transaction);
				}
				$view->setDataList($transactions);
				$view->setPaginate($this->page, $this->total_pages, $this->items_per_page);
				$this->response->setStatus(true);
				$this->response->setView($view);
				return $this->response;
			}
		}else{
			return authorization::FailResponse();
		}
	}
	function transaction_view($data){
		if($view = view::byName("\\packages\\financial\\views\\transactions\\view")){
			$types = authorization::childrenTypes();
			db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
			if($types){
				db::where("userpanel_users.type", $types, 'in');
			}else{
				db::where("userpanel_users.id", authentication::getID());
			}
			db::where("financial_transactions.id", $data['id']);
			$transaction = new transaction(db::getOne("financial_transactions", "financial_transactions.*"));
			if($transaction->id){
				$view->setData($transaction, 'transaction');
				$this->response->setStatus(true);
				$this->response->setView($view);
				return $this->response;
			}else{
				throw new NotFound;
			}
		}
	}
	private function getTransaction($id){
		$types = authorization::childrenTypes();
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		db::where("financial_transactions.id", $id);
		$transactionData = db::getOne("financial_transactions", "financial_transactions.*");
		if($transactionData){
			return new transaction($transactionData);
		}else{
			throw new NotFound;
		}
	}
	private function getPay($id){
		$types = authorization::childrenTypes();
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_pays.transaction", "LEFT");
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		db::where("financial_transactions_pays.id", $id);
		$payData = db::getOne("financial_transactions_pays", "financial_transactions_pays.*");
		if($payData){
			return new transaction_pay($payData);
		}else{
			throw new NotFound;
		}
	}
	private function getAvailablePayMethods(){
		$methods = array();
		$bankaccounts = bankaccount::where("status", 1)->has();
		$credit = authentication::getUser()->credit;
		$payports = payport::where("status", 1)->has();
		if($credit > 0){
			$methods[] = 'credit';
		}
		if($bankaccounts){
			$methods[] = 'banktransfer';
		}
		if($payports){
			$methods[] = 'onlinepay';
		}
		return $methods;
	}

	public function pay($data){
		if($view = view::byName("\\packages\\financial\\views\\transactions\\pay")){
			$transaction = $this->getTransaction($data['transaction']);
			if($transaction->status == transaction::unpaid){
				$view->setTransaction($transaction);
				foreach($this->getAvailablePayMethods() as $method){
					$view->setMethod($method);
				}
				$this->response->setStatus(true);
				$this->response->setView($view);
			}else{
				throw new NotFound;
			}
		}
		return $this->response;
	}
	public function payByCredit($data){
		if(in_array('credit',$this->getAvailablePayMethods())){
			if($view = view::byName("\\packages\\financial\\views\\transactions\\pay\\credit")){
				$transaction = $this->getTransaction($data['transaction']);
				if($transaction->status == transaction::unpaid){
					$user = authentication::getUser();
					$credit = $user->credit;
					$view->setTransaction($transaction);
					$view->setCredit($credit);
					$this->response->setStatus(false);
					if(http::is_post()){
						$inputsRoles = array(
							'credit' => array(
								'type' => 'number',
							)
						);
						try{
							$inputs = $this->checkinputs($inputsRoles);
							if($inputs['credit'] > 0 and $inputs['credit'] <= $transaction->payablePrice() and $inputs['credit'] <= $credit ){
								if($transaction->addPay(array(
									'method' => transaction_pay::credit,
									'price' => $inputs['credit']
								))){
									$user->credit -= $inputs['credit'];
									$user->save();
									$this->response->setStatus(true);
									$this->response->Go(userpanel\url('transactions/view/'.$transaction->id));
								}
							}else{
								throw new inputValidation('credit');
							}
						}catch(inputValidation $error){
							$view->setFormError(FormError::fromException($error));
						}
					}else{
						$view->setDataForm(min($transaction->payablePrice(), $credit), 'credit');
						$this->response->setStatus(true);
					}
					$this->response->setView($view);
				}else{
					throw new NotFound;
				}
			}
		}else{
			throw new NotFound;
		}
		return $this->response;
	}
	public function payByBankTransfer($data){
		if(in_array('banktransfer',$this->getAvailablePayMethods())){
			if($view = view::byName("\\packages\\financial\\views\\transactions\\pay\\banktransfer")){
				$transaction = $this->getTransaction($data['transaction']);
				if($transaction->status == transaction::unpaid){
					$view->setTransaction($transaction);
					$view->setBankAccounts(bankaccount::where("status", 1)->get());
					$this->response->setStatus(false);
					if(http::is_post()){
						$inputsRoles = array(
							'bankaccount' => array(
								'type' => 'number'
							),
							'price' => array(
								'type' => 'number',
							),
							'followup' => array(
								'type' => 'string'
							),
							'date' => array(
								'type' => 'date'
							)
						);
						try{
							$inputs = $this->checkinputs($inputsRoles);
							if($bankaccount = bankaccount::where("status", bankaccount::active)->byId($inputs['bankaccount'])){
								if($inputs['price'] > 0 and $inputs['price'] <= $transaction->payablePrice()){
									if(($inputs['date'] = date::strtotime($inputs['date'])) > date::time() - (86400*30)){
										if($transaction->addPay(array(
											'date' => $inputs['date'],
											'method' => transaction_pay::banktransfer,
											'price' => $inputs['price'],
											'status' => transaction_pay::pending,
											'params' => array(
												'bankaccount' => $bankaccount->id,
												'followup' => $inputs['followup']
											)
										))){
											$this->response->setStatus(true);
											$this->response->Go(userpanel\url('transactions/view/'.$transaction->id));
										}
									}else{
										throw new inputValidation("date");
									}
								}else{
									throw new inputValidation("price");
								}
							}else{
								throw new inputValidation("bankaccount");
							}
						}catch(inputValidation $error){
							$view->setFormError(FormError::fromException($error));
						}
					}else{
						$view->setDataForm($transaction->payablePrice(), 'price');
						$view->setDataForm(date::format("Y/m/d H:i:s"), 'date');
						$this->response->setStatus(true);
					}
					$this->response->setView($view);
				}else{
					throw new NotFound;
				}
			}
		}else{
			throw new NotFound;
		}
		return $this->response;
	}
	private function accept_handler($data, $newstatus){
		if(authorization::is_accessed('transactions_pays_accept')){
			$action = '';
			if($newstatus == transaction_pay::accepted){
				$action = 'accept';
			}elseif($newstatus == transaction_pay::rejected){
				$action = 'reject';
			}
			if($view = view::byName("\\packages\\financial\\views\\transactions\\pay\\".$action)){
				$pay = $this->getPay($data['pay']);
				$transaction = $pay->transaction;
				if($pay->status == transaction_pay::pending and $transaction->status == transaction::unpaid){
					$view->setPay($pay);
					$this->response->setStatus(false);
					if(http::is_post()){
						$inputsRoles = array(
							'confrim' => array(
								'type' => 'bool'
							)
						);
						try{
							$inputs = $this->checkinputs($inputsRoles);
							if($inputs['confrim']){
								$pay->status = $newstatus;
								if($newstatus == transaction_pay::accepted){
									$pay->setParam('acceptor', authentication::getID());
									$pay->setParam('accept_date', date::time());
								}elseif($newstatus == transaction_pay::rejected){
									$pay->setParam('rejector', authentication::getID());
									$pay->setParam('reject_date', date::time());
								}
								$pay->save();
								$this->response->setStatus(true);
								$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
							}else{
								throw new inputValidation("confrim");
							}
						}catch(inputValidation $error){
							$view->setFormError(FormError::fromException($error));
						}
					}else{
						$this->response->setStatus(true);
					}
					$this->response->setView($view);
				}else{
					throw new NotFound;
				}
			}
			return $this->response;
		}else{
			return authorization::FailResponse();
		}
	}
	public function acceptPay($data){
		return $this->accept_handler($data, transaction_pay::accepted);
	}
	public function rejectPay($data){
		return $this->accept_handler($data, transaction_pay::rejected);
	}
	public function onlinePay($data){
		if(in_array('onlinepay',$this->getAvailablePayMethods())){
			if($view = view::byName("\\packages\\financial\\views\\transactions\\pay\\onlinepay")){
				$transaction = $this->getTransaction($data['transaction']);
				if($transaction->status == transaction::unpaid){
					$payports = payport::where("status", payport::active)->get();
					$view->setTransaction($transaction);
					$view->setPayports($payports);
					$this->response->setStatus(false);
					if(http::is_post()){
						$inputsRoles = array(
							'payport' => array(
								'type' => 'number'
							),
							'price' => array(
								'type' => 'number',
							)
						);
						try{
							$inputs = $this->checkinputs($inputsRoles);
							if($payport = payport::byId($inputs['payport'])){
								if($inputs['price'] > 0 and $inputs['price'] <= $transaction->payablePrice()){
									$redirect = $payport->PaymentRequest($inputs['price'], $transaction);
									if($redirect->method == redirect::get){
										$this->response->Go($redirect->getURL());
									}
								}else{
									throw new inputValidation("price");
								}
							}else{
								throw new inputValidation("bankaccount");
							}
						}catch(inputValidation $error){
							$view->setFormError(FormError::fromException($error));
						}
					}else{
						$view->setDataForm($transaction->payablePrice(), 'price');
						$this->response->setStatus(true);
					}
					$this->response->setView($view);
				}else{
					throw new NotFound;
				}
			}
		}else{
			throw new NotFound;
		}
		return $this->response;
	}
	public function onlinePay_callback($data){
		if($view = view::byName("\\packages\\financial\\views\\transactions\\pay\\onlinepay\\error")){
			if($pay = payport_pay::byId($data['pay'])){
				if($pay->status == payport_pay::pending){
					$this->response->setStatus(false);
					$view->setPay($pay);
					try{
						if($pay->verification() == payport_pay::success){
							$pay->transaction->addPay(array(
								'date' => $inputs['date'],
								'method' => transaction_pay::onlinepay,
								'price' => $pay->price,
								'status' => transaction_pay::accepted,
								'params' => array(
									'payport_pay' => $pay->id,
								)
							));
							$this->response->setStatus(true);
							$this->response->Go(userpanel\url('transactions/view/'.$pay->transaction->id));
						}else{
							$view->setError('verification');
						}
					}catch(GatewayException $e){
						$view->setError('gateway');
					}catch(VerificationException $e){
						$view->setError('verification', $e->getMessage());
					}
					$this->response->setView($view);
				}else{
					throw new NotFound;
				}
			}else{
				throw new NotFound;
			}
		}
		return $this->response;
	}
}
class transactionNotFound extends NotFound{}
