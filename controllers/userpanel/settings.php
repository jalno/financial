<?php
namespace packages\financial\controllers\userpanel;
use packages\base\{inputValidation, translator};
use packages\financial\currency;
use packages\userpanel\{user, events\settings\Controller, events\settings\Log};

class settings implements Controller {
	public function store(array $inputs, user $user): array {
		$logs = array();
		if (isset($inputs["financial_transaction_currency"])) {
			$currency = currency::getDefault($user);
			$newCurrency = currency::byId($inputs["financial_transaction_currency"]);
			if (!$newCurrency) {
				throw new inputValidation("financial_transaction_currency");
			}
			if ($user->credit > 0 and $currency->id != $newCurrency->id) {
				$user->credit = $currency->changeTo($user->credit, $newCurrency);
				$user->save();
			}
			if ($currency->id != $newCurrency->id) {
				$logs[] = new Log("financial_transaction_currency", $currency->title, $newCurrency->title, translator::trans("financial.usersettings.transaction.currency"));
				$user->setOption("financial_transaction_currency", $inputs["financial_transaction_currency"]);
			}
		}
		return $logs;
	}
}
