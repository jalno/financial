<?php
namespace packages\financial\Validators;

use packages\base\{DB\Parenthesis, InputValidationException, Validator\ArrayValidator};
use packages\userpanel\{Authentication, User};
use packages\financial\{Authorization, Transaction};

class MergeTranactionsValidator extends ArrayValidator {
	public function getTypes(): array {
		return [];
	}

	/**
	 * @return Transaction[]
	 */
	public function validate(string $input, array $rule, $data): array {
		$me = Authentication::getID();
		$anonymous = Authorization::is_accessed("transactions_anonymous");
		$types = Authorization::childrenTypes();

		$rule['each'] = array(
			"type" => Transaction::class,
			"query" => function($query) use ($me, $anonymous, $types) {
				$query->where("financial_transactions.status", [Transaction::UNPAID, Transaction::PAID], "IN");
				$query->setQueryOption("MYSQLI_NESTJOIN");
				if ($anonymous) {
					$query->join(User::class, "user", "LEFT");
					$parenthesis = new Parenthesis();
					$parenthesis->where("userpanel_users.type",  $types, "IN");
					$parenthesis->orWhere("financial_transactions.user", null, "IS");
					$query->where($parenthesis);
				} else {
					$query->join(User::class, "user", "INNER");
					if ($types) {
						$query->where("userpanel_users.type", $types, "IN");
					} else {
						$query->where("userpanel_users.id", $me);
					}
				}
			}
		);
		$transactions = parent::validate($input, $rule, $data);
		$users = array_map(fn(Transaction $transaction) => $transaction->data['user'], $transactions);
		if (count(array_unique($users)) != 1) {
			throw new InputValidationException($input, "not-same-users");
		}
		return $transactions;
	}
}
