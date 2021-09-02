<?php
namespace packages\financial\controllers\userpanel;

use packages\base\{view\Error, InputValidationException, translator};
use packages\financial\{Authorization, Currency};
use packages\userpanel\{user, events\settings\Controller, events\settings\Log};

class settings implements Controller {

	public function store(array $inputs, user $user): array {
		return $this->handleStoreCurrency($inputs, $user);
	}

	/**
	 * handle change currency of a user in profile settings or user settings
	 * @param array $inputs that may contain 'financial_transaction_currency' index
	 * @param User $user that is the user we do action on him/her
	 *
	 * @return Log[] that is the log(s) of the action
	 */
	protected function handleStoreCurrency(array $inputs, User $user): array {
		$logs = array();

		$canChangeCurrency = Authorization::is_accessed("profile_change_currency");
		if (
			!$canChangeCurrency or
			!isset($inputs["financial_transaction_currency"])
		) {
			return $logs;
		}

		$newCurrency = (new Currency)->byID($inputs["financial_transaction_currency"]);
		if (!$newCurrency) {
			throw new InputValidationException("financial_transaction_currency");
		}

		$currency = Currency::getDefault($user);

		if ($newCurrency->id != $currency->id) {
			$freshUser = (new User)->byID($user->id);
			$oldCredit = $freshUser->credit;

			$user->credit = $currency->changeTo($oldCredit, $newCurrency);
			$saveUserResult = $user->save();
			if (!$saveUserResult) {
				$error = new Error("packages.financial.controller.userpanel.settings.change_currency_failed");
				$error->setMessage("error.{$error->getCode()}");
				throw $error;
			}

			$setOptionResult = $user->setOption(
				"financial_transaction_currency",
				$newCurrency->id
			);
			if (!$setOptionResult) {
				$user->credit = $oldCredit;
				$user->save();

				$error = new Error("packages.financial.controller.userpanel.settings.change_currency_failed");
				$error->setMessage("error.{$error->getCode()}");
				throw $error;
			}

			$logs[] = new Log(
				"financial_transaction_currency",
				$currency->title,
				$newCurrency->title,
				t("financial.usersettings.transaction.currency")
			);

			$freshUser = (new User)->byID($user->id);
			$logs[] = new Log(
				"financial_transaction_currency_credit",
				$oldCredit,
				$freshUser->credit,
				t("user.credit")
			);

		}

		return $logs;
	}
}
