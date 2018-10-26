<?php
namespace themes\clipone\listeners\financial;
use packages\base\{translator, db};
use packages\userpanel;
use packages\financial\{authorization, authentication, currency, transaction};
use themes\clipone\views\dashboard as view;
use themes\clipone\views\dashboard\shortcut;

class dashboard {
	public function initialize(){
		$this->addShortcuts();
	}
	protected function addShortcuts(){
		$user = authentication::getUser();
		if(authorization::is_accessed("transactions_list")){
			$types = authorization::childrenTypes();
			$transaction = new transaction();
			db::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "INNER");
			if ($types) {
				$transaction->where("userpanel_users.type", $types, "in");
			} else {
				$transaction->where("userpanel_users.id", $user->id);
			}
			$transaction->where("financial_transactions.status", transaction::unpaid);
			$transactions = $transaction->count();
			$shortcut = new shortcut("transactions");
			$shortcut->icon = "fa fa-money";
			if ($transactions) {
				$shortcut->title = $transactions;
				$shortcut->text = translator::trans("shortcut.transactions.unpaid.transaction");
				$shortcut->setLink(translator::trans("shortcut.transactions.link"), userpanel\url("transactions"));
			} else {
				$shortcut->text = translator::trans("shortcut.transactions.unpaid.transaction.iszere");
				if (authorization::is_accessed("transactions_addingcredit")) {
					$shortcut->setLink(translator::trans("transaction.adding_credit"), userpanel\url("transactions/addingcredit"));
				}
			}
			view::addShortcut($shortcut);
		}
		$shortcut = new shortcut("transactions.user.credit");
		$shortcut->icon = "fa fa-credit-card-alt";
		if ($user->credit > 0) {
			$shortcut->title = number_format($user->credit);
			$shortcut->text = currency::getDefault($user)->title . " " . translator::trans("shortcut.transactions.user.credit");
		} else {
			$shortcut->text = translator::trans("shortcut.transactions.user.credit.iszero");
		}
		if (authorization::is_accessed("transactions_addingcredit")) {
			$shortcut->setLink(translator::trans("transaction.adding_credit"), userpanel\url("transactions/addingcredit"));
		}
		view::addShortcut($shortcut);
	}
}
