<?php
namespace themes\clipone\Listeners\financial;
use packages\base\{Translator, DB};
use packages\userpanel;
use packages\financial\{Authorization, Authentication, Currency, Transaction};
use themes\clipone\Views\Dashboard as View;
use themes\clipone\Views\Dashboard\Shortcut;

class Dashboard {
	public function initialize(){
		$this->addShortcuts();
	}
	protected function addShortcuts(){
		$user = Authentication::getUser();
		if(Authorization::is_accessed("transactions_list")){
			$types = Authorization::childrenTypes();
			$transaction = new Transaction();
			DB::join("userpanel_users", "userpanel_users.id=financial_transactions.user", "INNER");
			if ($types) {
				$transaction->where("userpanel_users.type", $types, "in");
			} else {
				$transaction->where("userpanel_users.id", $user->id);
			}
			$transaction->where("financial_transactions.status", Transaction::unpaid);
			$transactions = $transaction->count();
			$shortcut = new Shortcut("transactions");
			$shortcut->icon = "fa fa-money";
			if ($transactions) {
				$shortcut->title = $transactions;
				$shortcut->text = Translator::trans("shortcut.transactions.unpaid.transaction");
				$shortcut->setLink(Translator::trans("shortcut.transactions.link"), userpanel\url("transactions"));
			} else {
				$shortcut->text = Translator::trans("shortcut.transactions.unpaid.transaction.iszere");
				if (Authorization::is_accessed("transactions_addingcredit")) {
					$shortcut->setLink(Translator::trans("transaction.adding_credit"), userpanel\url("transactions/addingcredit"));
				}
			}
			View::addShortcut($shortcut);
		}
		$shortcut = new Shortcut("transactions.user.credit");
		$shortcut->icon = "fa fa-credit-card-alt";
		if ($user->credit > 0) {
			$shortcut->title = number_format($user->credit);
			$shortcut->text = Currency::getDefault($user)->title . " " . Translator::trans("shortcut.transactions.user.credit");
		} else {
			$shortcut->text = Translator::trans("shortcut.transactions.user.credit.iszero");
		}
		if (Authorization::is_accessed("transactions_addingcredit")) {
			$shortcut->setLink(Translator::trans("transaction.adding_credit"), userpanel\url("transactions/addingcredit"));
		}
		View::addShortcut($shortcut);
	}
}
