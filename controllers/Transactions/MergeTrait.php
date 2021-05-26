<?php
namespace packages\financial\controllers\Transactions;

use packages\base\{Response, DB, InputValidationException, Date};
use packages\userpanel\{Log, User, Authentication};
use packages\financial\{Authorization, Transaction, validators};

trait MergeTrait {
	public function merge(): Response {
		Authorization::haveOrFail("transactions_merge");

		$inputs = $this->checkInputs(array(
			"transactions" => array(
				"type" => validators\MergeTranactionsValidator::class,
				"min" => 2,
			),
			"title" => array(
				"type" => "string",
			),
			"expire_at" => array(
				"type" => "date",
				"unix" => true,
				"optional" => true,
			),
		));
		if (!isset($inputs['expire_at'])) {
			$inputs['expire_at'] = max(array_map(fn($t) => $t->expire_at, $inputs['transactions']));
		}

		$mergedTransaction = new Transaction();
		$mergedTransaction->title = $inputs["title"];
		$mergedTransaction->create_at = Date::time();
		$mergedTransaction->expire_at = $inputs["expire_at"];
		foreach ($inputs["transactions"] as $t) {
			if ($t->data["user"]) {
				$mergedTransaction->user = $t->data["user"];
				break;
			}
		}
		$mergedTransaction->save();

		foreach ($inputs["transactions"] as $transaction) {
			DB::where("transaction", $transaction->id)
				->update("financial_transactions_pays", array(
					"transaction" => $mergedTransaction->id,
				));
			DB::where("transaction", $transaction->id)
				->update("financial_transactions_products", array(
					"transaction" => $mergedTransaction->id,
				));
			$transaction->delete();
		}

		$transactionsIDs = array_column($inputs["transactions"], "id");
		$log = new Log();
		$log->user = Authentication::getID();
		$log->type = logs\transactions\Merge::class;
		$log->title = t("financial.logs.transaction.pays.merge", array(
			"transaction_ids" => implode(", ", array_map(fn($id) => "#" . $id, $transactionsIDs)),
		));
		$log->parameters = array(
			"transactions" => $inputs["transactions"],
			"merged_transaction" => $mergedTransaction,
		);
		$log->save();

		$this->response->setData($mergedTransaction->toArray(true), "transaction");
		$this->response->setStatus(true);
		return $this->response;
	}
}
