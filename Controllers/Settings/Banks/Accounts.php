<?php
namespace packages\financial\Controllers\Settings\Banks;
use packages\userpanel;
use packages\base\{NotFound, Views\FormError, InputValidation, View\Error, DB\Parenthesis, Response};
use packages\financial\{View, Views, UserType, Controller, Authorization, Authentication, Bank, Bank\Account, PayPort, Validators};

class Accounts extends Controller{
	protected $authentication = true;
	public function search(): Response {
		Authorization::haveOrFail("settings_banks_accounts_search");
		$view = View::byName(Views\Settings\Banks\Accounts\Search::class);
		$types = Authorization::childrenTypes();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$inputsRules = array(
			"id" => array(
				"type" => "number",
				"optional" => true,
				"empty" => true,
			),
			"bank" => array(
				"type" => "number",
				"optional" => true,
				"empty" => true,
			),
			"user" => array(
				"type" => "number",
				"optional" => true,
				"empty" => true,
			),
			"account" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"cart" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"shaba" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"owner" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"status" => array(
				"values" => array(Account::Active, Account::WaitForAccept, Account::Rejected, Account::Deactive),
				"optional" => true,
				"empty" => true
			),
			"word" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true
			),
			"comparison" => array(
				"values" => array("equals", "startswith", "contains"),
				"default" => "contains",
				"optional" => true
			)
		);
		$inputs = $this->checkinputs($inputsRules);
		foreach (array_keys($inputsRules) as $item) {
			if (isset($inputs[$item]) and $inputs[$item] == "") {
				unset($inputs[$item]);
			}
		}
		foreach (array("id", "bank", "user", "owner", "account", "cart", "shaba", "status") as $item) {
			if (isset($inputs[$item])) {
				$comparison = $inputs["comparison"];
				$key = $item;
				if (in_array($item, array("id", "bank", "user"))) {
					$comparison = "equals";
					if ($item != "id") {
						$key .= "_id";
					}
				}
				$account->where("financial_banks_accounts.{$key}", $inputs[$item], $comparison);
			}
		}
		if (isset($inputs["word"])) {
			$parenthesis = new Parenthesis();
			foreach (array("owner", "account", "cart", "shaba") as $item) {
				if (!isset($inputs[$item])) {
					$parenthesis->orWhere("financial_banks_accounts.{$item}", $inputs[$item], $inputs["comparison"]);
				}
			}
			if (!$parenthesis->isEmpty()) {
				$account->where($parenthesis);
			}
		}
		$account->orderBy("financial_banks_accounts.id", "DESC");
		$accounts = $account->paginate($this->page, array("financial_banks_accounts.*", "financial_banks.*", "userpanel_users.*"));
		$this->total_pages = $account->totalPages;
		$view->setDataList($accounts);
		$view->setPaginate($this->page, $account->totalCount, $this->items_per_page);
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function add() {
		Authorization::haveOrFail("settings_banks_accounts_add");
		$view = View::byName(Views\Settings\Banks\Accounts\Add::class);
		$this->response->setView($view);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function store() {
		Authorization::haveOrFail("settings_banks_accounts_add");
		$view = View::byName(Views\Settings\Banks\Accounts\Add::class);
		$this->response->setView($view);
		$inputsRules = array(
			"bank" => array(
				"type" => "number",
			),
			"user" => array(
				"type" => "number",
				"optional" => true,
			),
			"owner" => array(
				"type" => "string",
			),
			"account" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"cart" => array(
				"regex" => "/^[0-9]{16,19}$/",
			),
			"shaba" => array(
				"type" => Validators\IBANValidator::class,
			),
		);
		$this->response->setStatus(false);
		$inputs = $this->checkinputs($inputsRules);
		if (!Authorization::childrenTypes()) {
			unset($inputs["user"]);
		}
		if (isset($inputs["user"])) {
			if (!userpanel\User::byId($inputs["user"])) {
				throw new InputValidation("user");
			}
		} else {
			$inputs["user"] = Authentication::getID();
		}
		$bank = new Bank();
		$bank->where("status", Bank::Active);
		if (!$bank->byId($inputs["bank"])) {
			throw new InputValidation("bank");
		}
		$account = new Account();
		foreach (array("bank", "user") as $item) {
			$account->{$item . "_id"} = $inputs[$item];
		}
		foreach (array("owner", "cart", "shaba") as $item) {
			$account->$item = $inputs[$item];
		}
		$account->account = (isset($inputs["account"]) and $inputs["account"]) ? $inputs["account"] : null;
		$account->save();
		$this->response->setStatus(true);
		$this->response->GO(userpanel\url("settings/financial/banks/accounts"));
		return $this->response;
	}
	public function edit(array $data) {
		Authorization::haveOrFail("settings_banks_accounts_edit");
		$types = Authorization::childrenTypes();
		$canAccept = Authorization::is_accessed("settings_banks_accounts_accept");
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		if (!$canAccept) {
			$account->where("financial_banks_accounts.status", Account::Rejected);
		}
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$view = view::byName(Views\Settings\Banks\Accounts\Edit::class);
		$this->response->setView($view);
		$view->setBankaccount($account);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function update(array $data): Response {
		Authorization::haveOrFail("settings_banks_accounts_edit");
		$types = Authorization::childrenTypes();
		$canAccept = Authorization::is_accessed("settings_banks_accounts_accept");
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		if (!$canAccept) {
			$account->where("financial_banks_accounts.status", Account::Rejected);
		}
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$view = view::byName(Views\Settings\Banks\Accounts\Edit::class);
		$this->response->setView($view);
		$view->setBankaccount($account);
		$inputsRules = array(
			"bank" => array(
				"type" => "number",
				"optional" => true
			),
			"user" => array(
				"type" => "number",
				"optional" => true
			),
			"owner" => array(
				"type" => "string",
				"optional" => true
			),
			"account" => array(
				"type" => "string",
				"optional" => true,
				"empty" => true,
			),
			"cart" => array(
				"regex" => "/^[0-9]{16,19}$/",
				"optional" => true,
			),
			"shaba" => array(
				"type" => Validators\IBANValidator::class,
				"optional" => true,
			),
		);
		$this->response->setStatus(false);
		$inputs = $this->checkinputs($inputsRules);
		if (!Authorization::childrenTypes()) {
			unset($inputs["user"]);
		}
		if (isset($inputs["user"])) {
			if ($inputs["user"]) {
				if (!userpanel\User::byId($inputs["user"])) {
					throw new InputValidation("user");
				}
			} else {
				unset($inputs["user"]);
			}
		}
		$hasChange = false;
		foreach (array("bank", "user") as $item) {
			$key = $item . "_id";
			if (isset($inputs[$item]) and $account->$key != $inputs[$item]) {
				$account->$key = $inputs[$item];
				$hasChange = true;
			}
		}
		foreach (array("owner", "cart", "shaba") as $item) {
			if (isset($inputs[$item]) and $account->$item != $inputs[$item]) {
				$account->$item = $inputs[$item];
				$hasChange = true;
			}
		}
		$account->account = (isset($inputs["account"]) and $inputs["account"]) ? $inputs["account"] : null;
		if ($hasChange and !$canAccept) {
			$account->status = Account::WaitForAccept;
		}
		$account->save();
		$this->response->setStatus(true);
		if ($canAccept) {
			$this->response->GO(userpanel\url("settings/financial/banks/accounts/edit/".$account->id));
		} else {
			$this->response->GO(userpanel\url("settings/financial/banks/accounts"));
		}
		return $this->response;
	}
	public function delete(array $data) {
		Authorization::haveOrFail("settings_banks_accounts_delete");
		$types = Authorization::childrenTypes();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$view = view::byName(Views\Settings\Banks\Accounts\Delete::class);
		$view->setBankaccount($account);
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function terminate(array $data): Response {
		Authorization::haveOrFail("settings_banks_accounts_delete");
		$types = Authorization::childrenTypes();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$view = View::byName(Views\Settings\Banks\Accounts\Delete::class);
		$view->setBankaccount($account);
		$this->response->setStatus(false);
		try {
			$payport = new PayPort();
			$payport->where("account", $account->id);
			$payport->where("status", PayPort::active);
			if ($payport->has()) {
				throw new payportDependencies();
			}
			$account->delete();
			$this->response->setStatus(true);
			$this->response->GO(userpanel\url("settings/financial/banks/accounts"));
		} catch (PayPortDependencies $error) {
			$error = new Error();
			$error->setType(error::FATAL);
			$error->setCode("financial.settings.Account.gatewayDependencies");
			$view->addError($error);
		}
		return $this->response;
	}
	public function accept(array $data): Response {
		Authorization::haveOrFail("settings_banks_accounts_accept");
		$types = Authorization::childrenTypes();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		$account->where("financial_banks_accounts.status", Account::Active, "!=");
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$account->status = Account::Active;
		$account->reject_reason = null;
		$account->oprator_id = Authentication::getID();
		$account->save();
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("settings/financial/banks/accounts"));
		return $this->response;
	}
	public function reject(array $data): Response {
		Authorization::haveOrFail("settings_banks_accounts_accept");
		$types = Authorization::childrenTypes();
		$account = new Account();
		$account->with("user");
		$account->with("bank");
		if ($types) {
			$account->where("userpanel_users.type", $types, "IN");
		} else {
			$account->where("financial_banks_accounts.user_id", Authentication::getID());
		}
		$account->where("financial_banks_accounts.id", $data["account"]);
		$account->where("financial_banks_accounts.status", Account::Rejected, "!=");
		if (!$account = $account->getOne(array("financial_banks_accounts.*", "userpanel_users.*", "financial_banks.*"))) {
			throw new NotFound;
		}
		$inputs = $this->checkinputs(array(
			"reason" => array(
				"type" => "string",
			),
		));
		$account->status = Account::Rejected;
		$account->reject_reason = $inputs["reason"];
		$account->oprator_id = Authentication::getID();
		$account->save();
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("settings/financial/banks/accounts"));
		return $this->response;
	}
}

class payportDependencies extends \Exception {}
