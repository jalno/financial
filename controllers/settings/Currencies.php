<?php
namespace packages\financial\controllers\settings;

use packages\base\{db, InputValidationException, NotFound, Response, view\Error, db\Parenthesis, Options, View};
use packages\userpanel\{user\Option};
use packages\financial\{Authorization, Controller, Currency, Transaction, views\settings\Currencies as views, validators};
use function packages\userpanel\url;

class Currencies extends Controller {
	public static function getCurrency($data): Currency {
		$currency = (new Currency)->byID($data["currency"]);
		if (!$currency) {
			throw new NotFound();
		}
		return $currency;
	}

	protected $authentication = true;

	public function search(){
		Authorization::haveOrFail("settings_currencies_search");
		$view = View::byName(views\Search::class);
		$this->response->setView($view);

		$inputs = $this->checkinputs(array(
			"id" => [
				"type" => "number",
				"optional" => true,
				"empty" => true,
			],
			"title" => [
				"type" => "string",
				"optional" => true,
				"empty" => true,
			],
			"word" => [
				"type" => "string",
				"optional" => true,
				"empty" => true
			],
			"comparison" => [
				"values" => ["equals", "startswith", "contains"],
				"default" => "contains",
				"optional" => true
			],
		));

		$model = new Currency();
		foreach (["id", "title"] as $item) {
			if (isset($inputs[$item]) and $inputs[$item]) {
				$comparison = $inputs["comparison"];
				if (in_array($item, ["id"])) {
					$comparison = "equals";
				}
				$model->where($item, $inputs[$item], $comparison);
			}
		}
		if (isset($inputs["word"]) and $inputs["word"]) {
			$parenthesis = new Parenthesis();
			foreach (["title"] as $item) {
				if (!isset($inputs[$item]) or !$inputs[$item]) {
					$parenthesis->where($item, $inputs["word"], $inputs["comparison"], "OR");
				}
			}
			$model->where($parenthesis);
		}

		$currencies = $model->paginate($this->page);
		$this->total_pages = $model->totalPages;
		$view->setDataList($currencies);
		$view->setPaginate($this->page, $model->totalCount, $this->items_per_page);

		$this->response->setStatus(true);
		return $this->response;
	}

	public function add() {
		Authorization::haveOrFail("settings_currencies_add");
		$view = View::byName(views\Add::class);
		$view->setCurrencies((new Currency)->get());
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}

	public function store(): Response {
		Authorization::haveOrFail("settings_currencies_add");
		$view = View::byName(views\Add::class);
		$this->response->setView($view);
		$view->setCurrencies(Currency::get());

		$inputs = $this->checkInputs(array(
			"title" => [
				"type" => validators\CurrencyTitleValidator::class,
			],
			"update_at" => [
				"type" => "date",
				"unix" => true,
			],
			"change" => [
				"type" => "bool",
				"optional" => true,
			],
			"rounding-behaviour" => [
				"type" => "number",
				"values" => [Currency::CEIL, Currency::ROUND, Currency::FLOOR],
				"optional" => true,
			],
			"rounding-precision" => [
				"type" => "int8",
				"zero" => true,
				"optional" => true,
			],
			"rates" => [
				"type" => validators\CurrencyRatesValidator::class,
				"optional" => true,
			],
		));
		if (isset($inputs["change"]) and $inputs["change"]) {
			foreach (["rounding-behaviour", "rounding-precision", "rates"] as $field) {
				if (!isset($inputs[$field])) {
					throw new InputValidationException($field);
				}
			}
		} else {
			unset($inputs["rates"]);
			unset($inputs["rounding-behaviour"]);
			unset($inputs["rounding-precision"]);
		}

		$currency = new Currency();
		$currency->title = $inputs["title"];
		$currency->update_at = $inputs["update_at"];
		if (isset($inputs["rounding-behaviour"])) {
			$currency->rounding_behaviour = $inputs["rounding-behaviour"];
		}
		if (isset($inputs["rounding-precision"])) {
			$currency->rounding_precision = $inputs["rounding-precision"];
		}
		$currency->save();
		if (isset($inputs["rates"])) {
			foreach ($inputs["rates"] as $rate) {
				$currency->addRate($rate["currency"], $rate["price"]);
			}
		}
		if (isset($inputs["default"]) and $inputs["default"]) {
			Options::save("packages.financial.defaultCurrency", $currency->id, true);
		}
		$this->response->setStatus(true);
		$this->response->Go(url("settings/financial/currencies/edit/{$currency->id}"));
		return $this->response;
	}

	public function edit($data): Response {
		Authorization::haveOrFail("settings_currencies_edit");
		$currency = self::getCurrency($data);
		$view = View::byName(views\Edit::class);
		$view->setCurrency($currency);
		$view->setCurrencies((new Currency())->where("id", $currency->id, "!=")->get());
		$this->response->setView($view);
		$this->response->setStatus(true);
		return $this->response;
	}

	public function update($data): Response {
		Authorization::haveOrFail("settings_currencies_edit");
		$currency = self::getCurrency($data);
		$view = View::byName(views\Edit::class);
		$view->setCurrency($currency);
		$view->setCurrencies((new Currency())->where("id", $currency->id, "!=")->get());
		$this->response->setView($view);

		$inputs = $this->checkInputs(array(
			"title" => [
				"type" => validators\CurrencyTitleValidator::class,
				"self" => $currency->id,
				"optional" => true,
			],
			"update_at" => [
				"type" => "date",
				"unix" => true,
				"optional" => true,
			],
			"change" => [
				"type" => "bool",
				"optional" => true,
			],
			"rounding-behaviour" => [
				"type" => "number",
				"values" => [Currency::CEIL, Currency::ROUND, Currency::FLOOR],
				"optional" => true,
			],
			"rounding-precision" => [
				"type" => "int8",
				"zero" => true,
				"optional" => true,
			],
			"rates" => [
				"type" => validators\CurrencyRatesValidator::class,
				"optional" => true,
			],
			"default" => [
				"type" => "bool",
				"optional" => true,
			],
		));

		if (isset($inputs["change"]) and $inputs["change"]) {
			if (!isset($inputs["rates"]) or !$inputs["rates"]) {
				throw new InputValidationException("rates");
			}
			if (!isset($inputs["rounding-behaviour"]) and !$currency->rounding_behaviour) {
				throw new InputValidationException("rounding-behaviour");
			}
		} else {
			unset($inputs["rates"]);
			unset($inputs["rounding-behaviour"]);
			unset($inputs["rounding-precision"]);
		}

		if (isset($inputs["change"]) and !$inputs["change"]) {
			$inputs["rates"] = [];
		}

        if (isset($inputs["rates"])) {
			$currencyRatesIDs = array_map(function ($item) {
				return $item["currency"]->id;
			}, $inputs["rates"]);

			foreach ($currency->rates as $rate) {
				$changeTo = $rate->changeTo;
				if (!in_array($changeTo->id, $currencyRatesIDs)) {
					$transaction = new Transaction();
					db::join("financial_transactions_products", "financial_transactions_products.transaction=financial_transactions.id", "LEFT");
					$transaction->where("financial_transactions.currency", $changeTo->id);
					$transaction->where("financial_transactions_products.currency", $currency->id);
					if ($transaction->has()) {
						$error = new Error();
						$error->setCode("financial.currencies.edit.dependenciesChangebleRateException");
						$error->setMessage(t("error.financial.currencies.edit.dependenciesChangebleRateException", ["currency" => $changeTo->title]));
						throw $error;
					}
				}
			}
		}
		if (isset($inputs["rates"])) {
			foreach ($currency->rates as $rate) {
				$changeTo = $rate->changeTo;
				if (!in_array($changeTo->id, $currencyRatesIDs)) {
					$rate->delete();
				}
			}
			foreach ($inputs["rates"] as $rate) {
				$currency->addRate($rate["currency"], $rate["price"]);
			}
		}
		foreach (["title", "update_at"] as $item) {
			if (!isset($inputs[$item])) {
				continue;
			}
			$currency->$item = $inputs[$item];
		}
		if (isset($inputs["rounding-behaviour"])) {
			$currency->rounding_behaviour = $inputs["rounding-behaviour"];
		}
		if (isset($inputs["rounding-precision"])) {
			$currency->rounding_precision = $inputs["rounding-precision"];
		}
		$currency->save();
		if (isset($inputs["default"]) and $inputs["default"]) {
			Options::save("packages.financial.defaultCurrency", $currency->id, true);
		}
		$this->response->setStatus(true);
		return $this->response;
	}

	public function delete($data){
		Authorization::haveOrFail("settings_currencies_delete");
		$currency = self::getCurrency($data);
		$view = View::byName(views\delete::class);
		$view->setCurrency($currency);
		$this->response->setView($view);
		$this->response->setStatus(true);
		return $this->response;
	}

	public function terminate($data) {
		Authorization::haveOrFail("settings_currencies_delete");
		$currency = self::getCurrency($data);
		$view = View::byName(views\Delete::class);
		$view->setCurrency($currency);
		$this->response->setView($view);

		$transaction = new Transaction();
		$transaction->where("currency", $currency->id);
		if ($transaction->has()) {
			throw new Error("financial.currencies.terminate.dependenciesTransactionException");
		}

		$option = new Option();
		$option->where("name", "financial_transaction_currency");
		$option->where("value", $currency->id);
		if ($option->has()) {
			throw new Error("financial.currencies.terminate.dependenciesUserCurrencyException");
		}
		$currency->delete();
		$this->response->setStatus(true);
		$this->response->Go(url("settings/financial/currencies"));
		return $this->response;
	}
}
