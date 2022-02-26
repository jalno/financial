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
		if(authorization::is_accessed("transactions_list")){
			$this->transActions($e->word);
		}
	}
	public function transActions($word){
		$anonymous = authorization::is_accessed("transactions_anonymous");

		db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", $anonymous ? "LEFT" : "INNER");
		db::join("financial_transactions_products", "financial_transactions_products.transaction=financial_transactions.id", "LEFT");
		$transaction = new transaction();
		$parenthesis = new parenthesis();
		foreach(array("title", "description") as $item){
			$parenthesis->where("financial_transactions_products.{$item}", $word, "contains", "OR");
		}
		foreach(array("title") as $item){
			$parenthesis->where("financial_transactions.{$item}", $word, "contains", "OR");
		}
		$transaction->where($parenthesis);
		foreach ($transaction->get(null, "financial_transactions.*") as $transaction) {
			$result = new link();
			$result->setLink(userpanel\url("transactions/view/{$transaction->id}"));
			$result->setTitle(translator::trans("financial.transactions", array(
				"title" => $transaction->title
			)));
			$result->setDescription(translator::trans("financial.transactions.description", array(
				"id" => $transaction->id,
				"create_at" => date::format("Y/m/d H:i:s", $transaction->create_at),
				"user" => $transaction->user ? $transaction->user->getFullName() : null,
			)));
			saerchHandler::addResult($result);
		}
	}
}
