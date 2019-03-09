<?php
namespace packages\financial\Bank;
use packages\userpanel\user;
use packages\base\db\dbObject;
use packages\financial\{Bank, authorization};

class Account extends dbObject {
	const Active = 1;
	const WaitForAccept = 2;
	const Rejected = 3;
	const Deactive = 4;
	protected $dbTable = "financial_banks_accounts";
	protected $primaryKey = "id";
	protected $dbFields = array(
        "bank_id" => array("type" => "text", "required" => true),
		"user_id" => array("type" => "int", "required" => true),
		"owner" => array("type" => "text", "required" => true),
        "account" => array("type" => "text"),
		"cart" => array("type" => "text"),
		"shaba" => array("type" => "text"),
		"oprator_id" => array("type" => "int"),
		"reject_reason" => array("type" => "text"),
		"status" => array("type" => "int", "required" => true)
	);
	protected $relations = array(
		"bank" => array("hasOne", Bank::class, "bank_id"),
		"user" => array("hasOne", user::class, "user_id"),
	);
	protected function preLoad(array $data): array {
		if (!isset($data["status"])) {
			$data["status"] = authorization::is_accessed("settings_banks_accounts_accept") ? Self::Active : Self::WaitForAccept;
		}
		return $data;
	}
}
