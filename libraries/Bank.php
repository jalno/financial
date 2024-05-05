<?php
namespace packages\financial;
use packages\base\DB\DBObject;

class Bank extends DBObject{
	const Active = 1;
	const Deactive = 2;
	protected $dbTable = "financial_banks";
	protected $primaryKey = "id";
	protected $dbFields = array(
        "title" => array("type" => "text", "required" => true, "unique" => true),
		"status" => array("type" => "int", "required" => true)
	);
	public function preLoad(array $data): array {
		if (!isset($data["status"])) {
			$data["status"] = Self::Active;
		}
		return $data;
	}
}
