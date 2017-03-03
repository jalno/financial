<?php
namespace packages\financial\listeners;
use \packages\base\db;
use \packages\base\db\parenthesis;
use \packages\base\translator;

use \packages\userpanel;
use \packages\userpanel\date;
use \packages\userpanel\events\search as event;
use \packages\userpanel\search as saerchHandler;
use \packages\userpanel\search\link;

use \packages\financial\transaction;
use \packages\financial\authorization;

class search{
	public function find(event $e){
		if(authorization::is_accessed('transactions_list')){
			$this->transActions($e->word);
		}
	}
	public function transActions($word){
		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "LEFT");
		db::join("financial_transactions_products", "financial_transactions_products.transaction=financial_transactions.id", "LEFT");
		$parenthesis = new parenthesis();
		foreach(array('name','lastname','email','cellphone','phone') as $item){
			$parenthesis->where("userpanel_users.{$item}", $word, 'contains', 'OR');
		}
		foreach(array('title', 'description', 'price') as $item){
			$parenthesis->where("financial_transactions_products.{$item}", $word, 'contains', 'OR');
		}
		foreach(array('title', 'price') as $item){
			$parenthesis->where("financial_transactions.{$item}", $word, 'contains', 'OR');
		}
		db::where($parenthesis);
		$transactions = array();
		foreach(db::get('financial_transactions', null, array('financial_transactions.*')) as $transaction){
			$transactions[] = new transaction($transaction);
		}
		foreach($transactions as $transaction){
			$result = new link();
			$result->setLink(userpanel\url('transactions'), array("id"=>$transaction->id));
			$result->setTitle(translator::trans("financial.transactions", array(
				'title' => $transaction->title
			)));
			$result->setDescription(translator::trans("financial.transactions.description", array(
				'create_at' => date::format("Y/m/d H:i:s", $transaction->create_at),
				'user' => $transaction->user->getFullName()
			)));
			saerchHandler::addResult($result);
		}
	}
}
