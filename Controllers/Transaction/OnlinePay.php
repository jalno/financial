<?php
namespace packages\financial\Controllers\Transaction;
use packages\base;
use packages\base\{View, Response, Translator, Date, NotFound, HTTP, DB};
use packages\financial\{Views, Logs, PayPortPay, PayPort\VerificationException, PayPort\GateWayException, Transaction, TransactionPay, Authorization, Authentication};
use packages\userpanel\{Controller, Log};

class OnlinePay extends Controller {
	public function __construct() {
		if (!Authentication::check()) {
			$token = HTTP::getURIData("token");
			if ($token) {
				$isValidToken = (new Transaction)->where("token", $token)->has();
				if (!$isValidToken) {
					parent::response(Authentication::FailResponse());
				}
			} else {
				parent::response(Authentication::FailResponse());
			}
		}
		parent::__construct();
	}
	public function callBack($data): Response {
		$pay = PayPortPay::byId($data["pay"]);
		if (!$pay or $pay->status != PayPortPay::pending) {
			throw new NotFound;
		}
		$this->response->setStatus(false);
		$view = View::byName(Views\Transactions\Pay\OnlinePay\Error::class);
		$this->response->setView($view);
		$view->setPay($pay);
		try {
			if ($pay->verification() != PayPortPay::success) {
				throw new VerificationException();
			}
			$this->response->setStatus(true);
			$transaction = $pay->transaction;
			$tPay = $transaction->addPay(array(
				"date" => Date::time(),
				"method" => TransactionPay::onlinepay,
				"price" => $pay->price,
				"currency" => $pay->currency,
				"status" => TransactionPay::accepted,
				"params" => array(
					"payport_pay" => $pay->id,
				)
			));
			if ($transaction->user) {
				$log = new Log();
				$log->user = $transaction->user;
				$log->type = Logs\Transactions\Pay::class;
				$log->title = Translator::trans("financial.logs.transaction.pay", ["transaction_id" => $pay->transaction->id]);
				$parameters["pay"] = TransactionPay::byId($tPay);
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
