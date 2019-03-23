<?php
namespace packages\financial\controllers\transaction;
use packages\base;
use packages\base\{controller, view, response, translator, date, NotFound, http, db};
use packages\financial\{views, logs, payport_pay, payport\VerificationException, payport\GatewayException, transaction, transaction_pay, authorization, authentication};
use packages\userpanel\log;

class OnlinePay extends controller {
	public function __construct() {
		$this->response = new response();
		if (authentication::check()) {
			$this->page = http::getURIData('page');
			$this->items_per_page = http::getURIData('ipp');
			if ($this->page < 1) $this->page = 1;
			if ($this->items_per_page < 1) $this->items_per_page = 25;
			db::pageLimit($this->items_per_page);
			$this->response = new response();
		} elseif ($token = http::getURIData("token")) {
			$transaction = new transaction();
			$transaction->where("token", $token);
			if (!$transaction = $transaction->getOne()) {
				parent::response(authentication::FailResponse());
			}
		} else {
			parent::response(authentication::FailResponse());
		}
	}
	public function callBack($data): response {
		$pay = payport_pay::byId($data["pay"]);
		if (!$pay) {
			throw new NotFound;
		}
		if ($pay->status != payport_pay::pending) {
			throw new NotFound;
		}
		$this->response->setStatus(false);
		$view = view::byName(views\transactions\pay\onlinepay\error::class);
		$this->response->setView($view);
		$view->setPay($pay);
		try {
			if($pay->verification() == payport_pay::success){
				$transaction = $pay->transaction;
				$tPay = $transaction->addPay(array(
					"date" => date::time(),
					"method" => transaction_pay::onlinepay,
					"price" => $pay->price,
					"currency" => $pay->currency,
					"status" => transaction_pay::accepted,
					"params" => array(
						"payport_pay" => $pay->id,
					)
				));
				if ($transaction->user) {
					$log = new log();
					$log->user = $transaction->user;
					$log->type = logs\transactions\pay::class;
					$log->title = translator::trans("financial.logs.transaction.pay", ["transaction_id" => $pay->transaction->id]);
					$parameters["pay"] = transaction_pay::byId($tPay);
					$parameters["currency"] = $pay->currency;
					$log->parameters = $parameters;
					$log->save();
				}
				$this->response->setStatus(true);
			} else {
				$view->setError("verification");
			}
		} catch(GatewayException $e) {
			$view->setError("gateway");
		} catch(VerificationException $e) {
			$view->setError("verification", $e->getMessage());
		}
		return $this->response;
	}
}
