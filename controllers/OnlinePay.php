<?php
namespace packages\financial\controllers\transaction;
use packages\base;
use packages\base\{view, response, translator, date, NotFound, http, db};
use packages\financial\{views, logs, payport_pay, payport\VerificationException, payport\GatewayException, transaction, transaction_pay, authorization, authentication};
use packages\userpanel\{Controller, log};

class OnlinePay extends Controller {
	public function __construct() {
		if (!Authentication::check()) {
			$token = http::getURIData("token");
			if ($token) {
				$isValidToken = (new Transaction)->where("token", $token)->has();
				if (!$isValidToken) {
					parent::response(Authentication::FailResponse());
				}
			} else {
				parent::response(Authentication::FailResponse());
			}
		}
	}
	public function callBack($data): response {
		$pay = payport_pay::byId($data["pay"]);
		if (!$pay or $pay->status != payport_pay::pending) {
			throw new NotFound;
		}
		$this->response->setStatus(false);
		$view = view::byName(views\transactions\pay\onlinepay\Error::class);
		$this->response->setView($view);
		$view->setPay($pay);
		try {
			if ($pay->verification() != payport_pay::success) {
				throw new VerificationException();
			}
			$this->response->setStatus(true);
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
		} catch(GatewayException $e) {
			$view->setError("gateway");
		} catch(VerificationException $e) {
			$view->setError("verification", $e->getMessage());
		}
		return $this->response;
	}
}
