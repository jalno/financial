<?php
namespace packages\financial\controllers;
use \packages\base;
use \packages\base\NotFound;
use \packages\base\http;
use \packages\base\db;
use \packages\financial\authorization;
use \packages\financial\authentication;
use \packages\financial\controller;
use \packages\financial\view;
use \packages\financial\transaction;
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
}
class transactionNotFound extends NotFound{}
