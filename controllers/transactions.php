<?php
namespace packages\financial\controllers;
use \packages\base;
use \packages\base\db;
use \packages\base\http;
use \packages\base\NotFound;
use \packages\base\translator;
use \packages\base\view\error;
use \packages\base\inputValidation;
use \packages\base\views\FormError;

use \packages\userpanel;
use \packages\userpanel\user;
use \packages\userpanel\date;

use \packages\financial\authorization;
use \packages\financial\authentication;
use \packages\financial\controller;
use \packages\financial\view;
use \packages\financial\transaction;
use \packages\financial\transaction_product;
use \packages\financial\transaction_pay;
use \packages\financial\bankaccount;
use \packages\financial\payport;
use \packages\financial\payport_pay;
use \packages\financial\payport\redirect;
use \packages\financial\payport\GatewayException;
use \packages\financial\payport\VerificationException;
use \packages\financial\payport\AlreadyVerified;
use \packages\financial\events;
class transactions extends controller{
	protected $authentication = true;
	function listtransactions(){
		authorization::haveOrFail('transactions_list');
		transaction::checkExpiration();
		$view = view::byName("\\packages\\financial\\views\\transactions\\listview");
		$types = authorization::childrenTypes();
		$transaction = new transaction;
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
			'name' => array(
				'type' => 'string',
				'optional' =>true,
				'empty' => true
			),
			'user' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true
			),
			'status' => array(
				'type' => 'number',
				'optional' => true,
				'empty' => true,
				'values' => [transaction::unpaid, transaction::paid, transaction::refund, transaction::expired]
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
		$searched = false;
		try{
			$inputs = $this->checkinputs($inputsRules);
			if(isset($inputs['user']) and $inputs['user'] != 0){
				$user = user::byId($inputs['user']);
				if(!$user){
					throw new inputValidation("user");
				}
				$inputs['user'] = $user->id;
			}
			foreach(array('id', 'title', 'status', 'user') as $item){
				if(isset($inputs[$item]) and $inputs[$item]){
					$comparison = $inputs['comparison'];
					if(in_array($item, array('id', 'status', 'user'))){
						$comparison = 'equals';
					}
					$transaction->where("financial_transactions.".$item, $inputs[$item], $comparison);
					$searched = true;
				}
			}
			if(isset($inputs['word']) and $inputs['word']){
				$parenthesis = new parenthesis();
				foreach(array('title') as $item){
					if(!isset($inputs[$item]) or !$inputs[$item]){
						$parenthesis->where($item,$inputs['word'], $inputs['comparison'], 'OR');
					}
				}
				$searched = true;
				$transaction->where($parenthesis);
			}
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
			$this->response->setStatus(false);
		}
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		if(!$searched){
			$transaction->where('financial_transactions.status', transaction::expired, '!=');
		}
		$transaction->orderBy('id', ' DESC');
		$transaction->pageLimit = $this->items_per_page;
		$transactions = $transaction->paginate($this->page, ["financial_transactions.*"]);
		$view->setDataList($transactions);
		$view->setPaginate($this->page, db::totalCount(), $this->items_per_page);
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	function transaction_view($data){
		$transaction = $this->getTransaction($data['id']);
		if($view = view::byName("\\packages\\financial\\views\\transactions\\view")){
			$view->setTransaction($transaction);
			$this->response->setStatus(true);
			$this->response->setView($view);
			return $this->response;
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
		$transaction = new transaction();
		$transaction->where("financial_transactions.id", $id);
		$transaction = $transaction->getOne("financial_transactions.*");
		if(!$transaction){
			throw new NotFound;
		}
		return $transaction;
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
	private function getAvailablePayMethods($canPayByCredit = true){
		$methods = array();
		$bankaccounts = bankaccount::where("status", 1)->has();
		$credit = authentication::getUser()->credit;
		$payports = payport::where("status", 1)->has();
		if($canPayByCredit and $credit > 0){
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
			$canPayByCredit = true;
			foreach($transaction->products as $product){
				if($product->type == '\packages\financial\products\addingcredit'){
					$canPayByCredit = false;
					break;
				}
			}
			if($transaction->status == transaction::unpaid){
				$view->setTransaction($transaction);
				foreach($this->getAvailablePayMethods($canPayByCredit) as $method){
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
									$redirect = $this->redirectToConfig($transaction);
									$this->response->Go($redirect ? $redirect : userpanel\url('transactions/view/'.$transaction->id));
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
			$view = view::byName("\\packages\\financial\\views\\transactions\\pay\\onlinepay");
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
								}elseif($redirect->method == redirect::post){
									$view = view::byName("\\packages\\financial\\views\\transactions\\pay\\onlinepay\\redirect");
									$view->setTransaction($transaction);
									$view->setRedirect($redirect);
								}
							}else{
								throw new inputValidation("price");
							}
						}else{
							throw new inputValidation("payport");
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
		}else{
			throw new NotFound;
		}
		return $this->response;
	}
	private function redirectToConfig($transaction){
		if($transaction->status == transaction::paid and !$transaction->isConfigured()){
			$count = 0;
			$needConfigProduct = null;
			foreach($transaction->products as $product){
				if(!$product->configure){
					$count++;
					$needConfigProduct = $product;
				}
				if($count > 1)break;
			}
			if($count == 1){
				return userpanel\url('transactions/config/'.$needConfigProduct->id);
			}
		}
		return null;
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
							$redirect = $this->redirectToConfig($pay->transaction);
							$this->response->Go($redirect ? $redirect : userpanel\url('transactions/view/'.$pay->transaction->id));
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
	protected function checkData($data){
		$types = authorization::childrenTypes();
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		db::where("financial_transactions.id", $data['id']);
		$transaction = new transaction(db::getOne("financial_transactions", "financial_transactions.*"));
		return ($transaction ? $transaction : new transactionNotFound);
	}
	public function delete($data){
		$view = view::byName("\\packages\\financial\\views\\transactions\\delete");
		authorization::haveOrFail('transactions_delete');
		$transaction = $this->checkData($data);
		$view->setTransactionData($transaction);
		$this->response->setStatus(false);
		if(http::is_post()){
			$transaction->delete();
			$this->response->setStatus(true);
			$this->response->Go(userpanel\url('transactions'));
		}else{
			$this->response->setStatus(true);
			$this->response->setView($view);
		}
		return $this->response;
	}
	public function edit($data){
		$view = view::byName("\\packages\\financial\\views\\transactions\\edit");
		authorization::haveOrFail('transactions_edit');
		$transaction = $this->checkData($data);

		$view->setTransactionData($transaction);
		$inputsRules = [
			'title' => [
				'type' => 'string',
				'optional' => true
			],
			'user' => [
				'type' => 'number',
				'optional' => true
			],
			'products' => [
				'optional' => true
			]
		];
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				if(isset($inputs['user'])){
					if(!user::byId($inputs['user'])){
						throw new inputValidation("user");
					}
				}
				if(isset($inputs['products'])){
					if(!is_array($inputs['products'])){
						throw new inputValidation('products');
					}
					foreach($inputs['products'] as $product){
						if(isset($product['id'])){
							if(!transaction_product::byId($product['id'])){
								throw new inputValidation("product");
							}
						}
						if($product['price'] < 0){
							throw new inputValidation("price");
						}
						if($product['discount'] < 0){
							throw new inputValidation("discount");
						}

					}
				}
				if(isset($inputs['products'])){
					if(!is_array($inputs['products'])){
						throw new inputValidation('products');
					}
					foreach($inputs['products'] as $row){
						if(isset($row['id'])){
							$product = transaction_product::byId($row['id']);
						}else{
							$product = new transaction_product;
							$product->transaction = $transaction->id;
							$product->method  = transaction_product::other;
						}
						$product->title = $row['title'];
						$product->description = $row['description'];
						$product->number = $row['number'];
						$product->price = $row['price'];
						$product->discount = $row['discount'];
						$product->save();

					}
				}
				foreach(['title', 'user'] as $item){
					if(isset($inputs[$item])){
						$transaction->$item = $inputs[$item];
					}
				}
				if(isset($inputs['description'])){
					$transaction->setparam('description', $inputs['description']);
				}
				if($transaction->status == transaction::unpaid){
					if(isset($inputs['untriggered'])){
						$transaction->setParam('trigered_paid', 0);
					}
				}
				$transaction->save();
				$event = new events\transactions\edit($transaction);
				$event->trigger();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function add(){
		$view = view::byName("\\packages\\financial\\views\\transactions\\add");
		authorization::haveOrFail('transactions_add');
		$inputsRules = array(
			'title' => array(
				'type' => 'string'
			),
			'user' => array(
				'type' => 'number'
			),
			'create_at' => array(
				'type' => 'date'
			),
			'expire_at' => array(
				'type' => 'date'
			),
			'products' => array()
		);
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				$inputs['user'] = user::byId($inputs['user']);
				$inputs['create_at'] = date::strtotime($inputs['create_at']);
				$inputs['expire_at'] = date::strtotime($inputs['expire_at']);

				if(!$inputs['user']){
					throw new inputValidation("user");
				}
				if($inputs['create_at'] <= 0){
					throw new inputValidation("create_at");
				}
				if($inputs['expire_at'] < $inputs['create_at']){
					throw new inputValidation("expire_at");
				}
				$products = array();
				foreach($inputs['products'] as $x => $product){
					if(!isset($product['title'])){
						throw new inputValidation("products[$x][title]");
					}
					if(!isset($product['price']) or $product['price'] <= 0){
						throw new inputValidation("products[$x][price]");
					}
					if(isset($product['discount'])){
						if($product['discount'] < 0){
							throw new inputValidation("products[$x][discount]");
						}
					}else{
						$product['discount'] = 0;
					}
					if(isset($product['number'])){
						if($product['number'] < 0){
							throw new inputValidation("products[$x][number]");
						}
					}else{
						$product['number'] = 1;
					}
					$products[] = array(
						'title' => $product['title'],
						'price' => $product['price'],
						'discount' => $product['discount'],
						'description' => $product['description'],
						'number' => $product['number'],
						'method' => transaction_product::other
					);

				}

				$transaction = new transaction;
				foreach($products as $product){
					$transaction->addProduct($product);
				}
				$transaction->title = $inputs['title'];
				$transaction->user = $inputs['user']->id;
				$transaction->create_at = $inputs['create_at'];
				$transaction->expire_at = $inputs['expire_at'];

				$transaction->save();
				if(isset($inputs['description'])){
					$transaction->setparam('description', $inputs['description']);
				}
				$event = new events\transactions\add($transaction);
				$event->trigger();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/view/'.$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	private function getProduct($data){
		$types = authorization::childrenTypes();
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_products.transaction", "inner");
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "inner");
		if($types){
			db::where("userpanel_users.type", $types, 'in');
		}else{
			db::where("userpanel_users.id", authentication::getID());
		}
		$product = new transaction_product();
		$product->where("financial_transactions_products.id", $data['id']);
		$product = $product->getOne("financial_transactions_products.*");
		if(!$product){
			throw new NotFound();
		}
		return $product;
	}
	public function product_delete($data){
		authorization::haveOrFail('transactions_product_delete');
		$transaction_product = $this->getProduct($data);
		$view = view::byName("\\packages\\financial\\views\\transactions\\product_delete");
		$view->setProduct($transaction_product);
		if(http::is_post()){
			$this->response->setStatus(false);
			try {
				$transaction = $transaction_product->transaction;
				if(count($transaction->products) < 2){
					throw new illegalTransaction();
				}
				$transaction_product->delete();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
			}catch(illegalTransaction $e){
				$error = new error();
				$error->setCode('illegalTransaction');
				$view->addError($error);
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function pay_delete($data){
		authorization::haveOrFail('transactions_pay_delete');
		db::join("financial_transactions", "financial_transactions.id=financial_transactions_pays.transaction", "LEFT");
		$transaction_pay = new transaction_pay();
		$transaction_pay->where("financial_transactions_pays.id", $data['id']);
		$transaction_pay = $transaction_pay->getOne("financial_transactions_pays.*");
		if(!$transaction_pay){
			throw new NotFound();
		}
		$view = view::byName("\\packages\\financial\\views\\transactions\\pay\\delete");
		$view->setPayData($transaction_pay);
		if(http::is_post()){
			$this->response->setStatus(false);
			$inputsRoles = [
				'untriggered' => [
					'type' => 'number',
					'values' => [1],
					'optional' => true,
					'empty' => true
				]
			];
			try{
				$inputs = $this->checkinputs($inputsRoles);
				$transaction = $transaction_pay->transaction;
				if(count($transaction->pays) == 1){
					if(isset($inputs['untriggered']) and $inputs['untriggered']){
						$transaction->deleteParam('trigered_paid');
					}
				}
				$transaction_pay->delete();
				if($transaction->payablePrice() > 0){
					$transaction->status = transaction::unpaid;
				}
				$transaction->save();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url('transactions/edit/'.$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function addingcredit(){
		$view = view::byName("\\packages\\financial\\views\\transactions\\addingcredit");
		authorization::haveOrFail('transactions_addingcredit');
		$types = authorization::childrenTypes();
		if($types){
			$view->setClient(authentication::getID());
		}
		$inputsRules = array(
			'price' => array(
				'type' => 'number'
			)
		);
		if($types){
			$inputsRules['client'] = array(
				'type' => 'number'
			);
		}
		$this->response->setStatus(false);
		if(http::is_post()){
			try{
				$inputs = $this->checkinputs($inputsRules);
				if(isset($inputs['client'])){
					if($inputs['client']){
						if(!$inputs['client'] = user::byId($inputs['client'])){
							throw new inputValidation('client');
						}
					}else{
						unset($inputs['client']);
					}
				}
				if($inputs['price'] <= 0){
					throw new inputValidation('price');
				}
				$transaction = new transaction;
				$transaction->title = translator::trans("transaction.adding_credit");
				$transaction->user = $inputs['client']->id;
				$transaction->create_at = time();
				$transaction->expire_at = time()+86400;
				$transaction->addProduct(array(
					'title' => translator::trans("transaction.adding_credit", array('price' => $inputs['price'])),
					'price' => $inputs['price'],
					'type' => '\packages\financial\products\addingcredit',
					'discount' => 0,
					'number' => 1,
					'method' => transaction_product::addingcredit
				));
				$transaction->save();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
			var_dump($this->inputsvalue($inputsRules));
			$view->setDataForm($this->inputsvalue($inputsRules));
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function accepted($data){
		authorization::haveOrFail('transactions_accept');
		$view = view::byName("\\packages\\financial\\views\\transactions\\accept");
		$transaction = transaction::byId($data['id']);
		if(!$transaction or $transaction->status != transaction::unpaid){
			throw new NotFound;
		}
		$view->setTransactionData($transaction);
		if(http::is_post()){
			try{
				$transaction->addPay(array(
					'date' => time(),
					'method' => transaction_pay::payaccepted,
					'price' => $transaction->price,
					'status' => transaction_pay::accepted,
					'params' => array(
						'acceptor' => authentication::getID(),
						'accept_date' => time(),
					)
				));
				$transaction->status = transaction::paid;
				$transaction->save();
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url("transactions/view/".$transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
	public function config($data){
		authorization::haveOrFail('transactions_product_config');
		$product = $this->getProduct($data);
		$view = view::byName("\\packages\\financial\\views\\transactions\\product\\config");
		if($product->configure){
			throw new NotFound();
		}
		$product->config();
		$view->setProduct($product);
		if(http::is_post()){
			$this->response->setStatus(false);
			try{
				if($inputsRules = $product->getInputs()){
					$inputs = $this->checkinputs($inputsRules);
				}
				$product->config($inputs);
				$this->response->setStatus(true);
				$this->response->Go(userpanel\url("transactions/view/".$product->transaction->id));
			}catch(inputValidation $error){
				$view->setFormError(FormError::fromException($error));
			}
		}else{
			$this->response->setStatus(true);
		}
		$this->response->setView($view);
		return $this->response;
	}
}
class transactionNotFound extends NotFound{}
class illegalTransaction extends \Exception{}