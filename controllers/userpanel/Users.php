<?php
namespace packages\financial\controllers\userpanel;

use packages\userpanel\{controllers\Users as UserpanelUsers, User\Option, Authentication};
use packages\financial\{Controller, Currency, Transaction};
use packages\base\NotFound;
use packages\base\Response;

class Users extends Controller
{
	protected bool $authentication = true;

	public function search($data): Response {
		$response = (new UserpanelUsers())->search($data);
		$dataList = $response->getView()->getDataList();
		$userIds = array();
		foreach ($dataList as $key => $user) {
			$userIds[$key] = $user->id;
		}
		if (!$userIds) {
			return $response;
		}
		$defaultCurrency = Currency::getDefault();
		$currencies = array();
		$currencies[$defaultCurrency->id] = $defaultCurrency;
		$option = new Option();
		$option->where("user", $userIds, "IN");
		$option->where("name", "financial_transaction_currency");
		foreach ($option->get(null, "userpanel_users_options.*") as $option) {
			$key = array_search($option->data["user"], $userIds);
			if ($key !== false) {
				if (!isset($currencies[$option->value])) {
					$currencies[$option->value] = Currency::byId($option->value);
				}
				$dataList[$key]->currency = $currencies[$option->value]->title;
				unset($userIds[$key]);
			}
		}
		foreach ($userIds as $key => $user) {
			$dataList[$key]->currency = $defaultCurrency->title;
		}
		$response->getView()->setDataList($dataList);
		return $response;
	}

	public function getCheckoutLimits(array $data): Response
	{
		$query = UserpanelUsers::checkUserAccessibility();
		$query->where('id', $data['user']);

		$user = $query->getOne();

		if (!$user) {
			throw new NotFound();
		}

		$limits = Transaction::getCheckoutLimits($user->id);

		$this->response->setStatus(true);
		$this->response->setData([
			'price' => $limits['price'] ?? 0,
			'period' => $limits['period'] ?? 0,
			'last_time' => (int) $user->option('financial_last_checkout_time'),
		]);

		return $this->response;
	}

	public function getCheckoutLimitsForUser(): Response
	{
		return $this->getCheckoutLimits([
			'user' => Authentication::getID(),
		]);
	}
}
