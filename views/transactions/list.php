<?php
namespace packages\financial\views\transactions;

use packages\base\{DB\DBObject, view\Error, views\traits\Form as FormTrait};
use packages\financial\{Authorization, Currency, views\ListView as ParentListView, Transaction};
use packages\userpanel\{Authentication};

class ListView extends ParentListView {
	use formTrait;

	protected static $navigation;

	public static function onSourceLoad(){
		self::$navigation = authorization::is_accessed('transactions_list');
	}

	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	protected $canAddingCredit;

	public function __construct() {
		$this->canAddingCredit = authorization::is_accessed('transactions_addingcredit');
		$this->canAdd = authorization::is_accessed('transactions_add');
		$this->canView = authorization::is_accessed('transactions_view');
		$this->canEdit = authorization::is_accessed('transactions_edit');
		$this->canDel = authorization::is_accessed('transactions_delete');
	}
	public function export(): array {
		$export = array(
			'data' => array(
				'items' => DBObject::objectToArray($this->dataList, true),
				'items_per_page' => (int)$this->itemsPage,
				'current_page' => (int)$this->currentPage,
				'total_items' => (int)$this->totalItems,
			),
		);

		$me = Authentication::getUser();
		$userCurrency = Currency::getDefault($me);
		$userCurrencyArray = $userCurrency->toArray();

		$export["data"]["balance"] = array(
			"amount" => $me->credit,
			"currency" => $userCurrencyArray,
		);

		$unpaidTransactions = (new Transaction)
			->where("user", $me->id)
			->where("status", Transaction::UNPAID)
		->get();

		$debt = 0;
		$error = null;
		foreach ($unpaidTransactions as $t) {
			try {
				$debt += $t->currency->changeTo($t->price, $userCurrency);
			} catch (Currency\UnChangableException $e) {
				if (!$error) {
					$error = new Error("packages.financial.views.transactions.ListView.export.debt.unchangeable_price_exception");
					$error->setTraceMode(Error::NO_TRACE);
				}
			}
		}
		$export["data"]["debt"] = array(
			"amount" => $debt,
			"currency" => $userCurrencyArray,
		);
		if ($error) {
			$export["data"]["debt"]["error"] = [$error];
		}

		return $export;
	}
	protected function getTransactions():array{
		return $this->dataList;
	}
}
