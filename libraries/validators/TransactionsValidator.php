<?php
namespace packages\financial\validators;

use packages\base\{db\DuplicateRecord, InputValidationException, Validator\IValidator, DB\Parenthesis};
use packages\financial\{Authentication, Authorization, Transaction};
use packages\userpanel\{User};

class TransactionsValidator implements IValidator {
	public function getTypes(): array {
		return [];
	}

	/**
	 * validate transactions
	 *
	 * @param string $input
	 * @param array $rule that can be like this:
	 * 	array(
	 * 		[empty]: bool
	 * 		[default]: mixed
	 * 		[strict-array]: bool that indicates data should be sent as array
	 * 		[query]: callable that call the last before get query
	 * 		[limit]: int|array
	 * 		[fields]: string|array that fields you need to get
	 * 	)
	 */
	public function validate(string $input, array $rule, $data): array {

		if (empty($data)) {
			if (!isset($rule['empty']) or !$rule['empty']) {
				throw new InputValidationException($input);
			}
			if (isset($rule['default'])) {
				return $rule['default'];
			}
			return [];
		}

		if (!is_array($data)) {
			if (isset($rule["strict-array"]) and $rule["strict-array"]) {
				throw new InputValidationException($input);
			} else {
				$data = array($data);
			}
		}

		foreach ($data as $key => $transactionID) {
			if (empty($transactionID) or !is_numeric($transactionID)) {
				throw new InputValidationException("{$input}[{$key}]", "not-valid-id");
			}
			foreach ($data as $key1 => $tID) {
				if ($key != $key1 and $transactionID == $tID) {
					throw new DuplicateRecord("{$input}[{$key1}]");
				}
			}
		}

		$meID = Authentication::getID();
		$types = Authorization::childrenTypes();
		$anonymous = Authorization::is_accessed("transactions_anonymous");

		$transactions = new Transaction();
		$transactions->where("financial_transactions.id", $data, "IN");
		if ($anonymous) {
			$transactions->join(User::class, "user", "LEFT");
			$p = new Parenthesis();
			$p->where("userpanel_users.type",  $types, "IN");
			$p->orWhere("financial_transactions.user", null, "IS");
			$transactions->where($p);
		} else {
			$transactions->join(User::class, "user", "INNER");
			if ($types) {
				$transactions->where("userpanel_users.type", $types, "IN");
			} else {
				$transactions->where("userpanel_users.id", $meID);
			}
		}

		if (isset($rule["query"])) {
			$rule["query"]($transactions);
		}

		$limit = $rule["limit"] ?? null;
		$fields = $rule["fields"] ?? ["financial_transactions.*"];

		$transactions = $transactions->get($limit, $fields);

		if (count($transactions) != count($data)) {
			$tdbIDs = array_column($transactions, "id");
			foreach ($data as $key => $transactionID) {
				if (!in_array($transactionID, $tdbIDs)) {
					throw new InputValidationException("{$input}[{$key}]", "missing-transaction");
				}
			}
		}

		return $transactions;
	}
}
