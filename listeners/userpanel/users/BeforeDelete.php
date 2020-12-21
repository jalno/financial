<?php
namespace packages\financial\listeners\userpanel\users;

use packages\base\{View\Error};
use packages\financial\{Bank\Account, Authorization, Transaction};
use packages\userpanel\events as UserpanelEvents;
use function packages\userpanel\url;

class BeforeDelete {
	public function check(UserpanelEvents\Users\BeforeDelete $event): void {
		$this->checkTransactions($event);
		$this->checkBankAccountsUser($event);
	}
	private function checkTransactions(UserpanelEvents\Users\BeforeDelete $event): void {
		$user = $event->getUser();
		$hasTransaction = (new Transaction)->where("user", $user->id)->has();
		if (!$hasTransaction) {
			return;
		}
		$message = t("error.packages.financial.error.transactions.user.delete_user_warn.message");
		$error = new Error("packages.financial.error.transactions.user.delete_user_warn");
		$error->setType(Error::WARNING);
		if (Authorization::is_accessed("transactions_list")) {
			$message .= "<br> " . t("packages.financial.error.transactions.user.delete_user_warn.view_transactions") . " ";
			$error->setData(array(
				array(
					"txt" => '<i class="fa fa-search"></i> ' . t("packages.financial.error.transactions.user.delete_user_warn.view_transactions_btn"),
					"type" => "btn-warning",
					"link" => url("transactions", array(
						"user" => $user->id,
					)),
				),
			), "btns");
		} else {
			$message .= "<br> " . t("packages.financial.error.transactions.user.delete_user_warn.view_transactions.tell_someone");
		}
		$error->setMessage($message);

		$event->addError($error);
	}
	private function checkBankAccountsUser(UserpanelEvents\Users\BeforeDelete $event): void {
		$user = $event->getUser();
		$hasBankAccount = (new Account)->where("user_id", $user->id)->has();
		if (!$hasBankAccount) {
			return;
		}
		$message = t("error.packages.financial.error.banks_accounts.user.delete_user_warn.message");
		$error = new Error("packages.financial.error.banks_accounts.user.delete_user_warn");
		$error->setType(Error::WARNING);
		if (Authorization::is_accessed("settings_banks_accounts_search")) {
			$message .= "<br> " . t("packages.financial.error.banks_accounts.user.delete_user_warn.view_banks_accounts") . " ";
			$error->setData(array(
				array(
					"txt" => '<i class="fa fa-search"></i> ' . t("packages.financial.error.banks_accounts.user.delete_user_warn.view_banks_accounts_btn"),
					"type" => "btn-warning",
					"link" => url("settings/financial/banks/accounts", array(
						"user" => $user->id,
					)),
				),
			), "btns");
		} else {
			$message .= "<br> " . t("packages.financial.error.banks_accounts.user.delete_user_warn.view_banks_accounts.tell_someone");
		}
		$error->setMessage($message);

		$event->addError($error);
	}
}
