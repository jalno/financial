<?php
namespace packages\financial\Controllers\Settings;
use packages\base\{Response, NotFound};
use themes\clipone\views\financial as Views;
use packages\financial\{Controller, View, Authorization, Bank};

class Banks extends Controller {
	protected $authentication = true;
	public function search(): Response {
		Authorization::haveOrFail("settings_banks_search");
		$view = view::byName(Views\Settings\Banks\Search::class);
		$this->response->setView($view);
		$inputs = $this->checkinputs(array(
			"id" => array(
				"type" => "number",
				"optional" => true,
				"empty" => true,
			),
			"title" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"status" => array(
				"values" => array(Bank::Active, Bank::Deactive),
				"optional" => true,
				"empty" => true,
			),
			"comparison" => array(
				"values" => array("equals", "startswith", "contains"),
				"default" => "contains",
				"optional" => true
			),
		));
		foreach (array("id", "title", "status") as $item) {
			if (isset($inputs[$item]) and $inputs[$item] == "") {
				unset($inputs[$item]);
			}
		}
		$bank = new Bank();
		foreach (array("id", "title", "status") as $item) {
			if (isset($inputs[$item])) {
				$comparison = $inputs["comparison"];
				if (in_array($item, array("id", "status"))) {
					$comparison = "equals";
				}
				$bank->where($item, $inputs[$item], $comparison);
			}
		}
		$bank->orderBy("id", "DESC");
		$bank->pageLimit = $this->items_per_page;
		$banks = $bank->paginate($this->page);
		$this->total_pages = $bank->totalPages;
		$view->setDataList($banks);
		$view->setPaginate($this->page, $bank->totalCount, $this->items_per_page);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function store(): Response {
		Authorization::haveOrFail("settings_banks_add");
		$inputs = $this->checkinputs(array(
			"title" => array(
				"type" => "string",
			),
		));
		$bank = new Bank($inputs);
		$bank->save();
		$this->response->setData($bank->toArray(), "bank");
		$this->response->setStatus(true);
		return $this->response;
	}
	public function update(array $data): Response {
		Authorization::haveOrFail("settings_banks_edit");
		if (!$bank = Bank::byId($data["bank"])) {
			throw new NotFound();
		}
		$inputs = $this->checkinputs(array(
			"title" => array(
				"type" => "string",
				"optional" => true,
			),
			"status" => array(
				"values" => array(Bank::Active, Bank::Deactive),
				"optional" => true,
			),
		));
		if (isset($inputs["title"]) or isset($inputs["status"])) {
			$bank->save($inputs);
		}
		$this->response->setStatus(true);
		$this->response->setData($bank->toArray(), "bank");
		return $this->response;
	}
	public function terminate(array $data): Response {
		Authorization::haveOrFail("settings_banks_delete");
		if (!$bank = Bank::byId($data["bank"])) {
			throw new NotFound();
		}
		$bank->delete();
		$this->response->setStatus(true);
		return $this->response;
	}
}
