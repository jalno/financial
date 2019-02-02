<?php
namespace packages\financial\controllers\userpanel;
use packages\userpanel\{controllers\users as userpanelUsers, user\option};
use packages\financial\{controller, currency};
use packages\base\{response, inputValidation};

class Users extends controller {
	public function search($data): response {
		$users = new userpanelUsers();
		$response = $users->index($data);
		$view = $response->getView();
		$dataList = $view->getDataList();
		$defaultCurrency = currency::getDefault();
		$userIds = array();
		foreach ($dataList as $key => $user) {
			$userIds[$key] = $user->id;
		}
		if (!$userIds) {
			return $response;
		}
		$currencies = array();
		$currencies[$defaultCurrency->id] = $defaultCurrency;
		$option = new option();
		$option->where("user", $userIds, "IN");
		$option->where("name", "financial_transaction_currency");
		foreach ($option->get(null, "userpanel_users_options.*") as $option) {
			$key = array_search($option->data["user"], $userIds);
			if ($key !== false) {
				if (!isset($currencies[$option->value])) {
					$currencies[$option->value] = currency::byId($option->value);
				}
				$dataList[$key]->currency = $currencies[$option->value]->title;
				unset($userIds[$key]);
			}
		}
		foreach ($userIds as $key => $user) {
			$dataList[$key]->currency = $defaultCurrency->title;
		}
		$view->setDataList($dataList);
		return $response;
	}
}
