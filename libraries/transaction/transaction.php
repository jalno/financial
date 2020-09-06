<?php
namespace packages\financial;

use packages\base\{options, db\dbObject, packages, translator};
use packages\userpanel\{user, date};
use packages\dakhl\API as dakhl;

class transaction extends dbObject{
	const unpaid = 1;
	const paid = 2;
	const refund = 3;
	const expired = 4;
	const rejected = 5;

	public static function generateToken(int $length = 15): string {
		$numberChar = "0123456789";
		$pw = "";
		while (strlen($pw) < $length) {
			$pw .= substr($numberChar, rand(0, 9), 1);
		}
		return $pw;
	}

	protected $dbTable = "financial_transactions";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'token' => array('type' => "text", "required" => true, "unique" => true),
        'user' => array('type' => 'int'),
        'title' => array('type' => 'text', 'required' => true),
        'price' => array('type' => 'double', 'required' => true),
		'create_at' => array('type' => 'int', 'required' => true),
		'expire_at' => array('type' => 'int'),
		'paid_at' => array('type' => 'int'),
		'currency' => array('type' => 'int', 'required' => true),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'user' => array('hasOne', 'packages\\userpanel\\user', 'user'),
		'currency' => array('hasOne', 'packages\\financial\\currency', 'currency'),
		'params' => array('hasMany', 'packages\\financial\\transaction_param', 'transaction'),
		'products' => array('hasMany', 'packages\\financial\\transaction_product', 'transaction'),
		'pays' => array('hasMany', 'packages\\financial\\transaction_pay', 'transaction'),
	);
	protected $tmproduct = array();
	protected $tmpays = array();

	public function expire() {
		$payablePrice = $this->payablePrice();
		if ($payablePrice < 0) {
			$payablePrice *= -1;
			$userCurrency = Currency::getDefault($this->user);
			$price = $this->currency->changeTo($payablePrice, $userCurrency);
			$this->user->credit += $price;
			$this->user->save();
		} else {
			$this->returnPaymentsToCredit([Transaction_Pay::credit, Transaction_Pay::onlinepay, Transaction_Pay::banktransfer]);
		}
		$this->status = self::expired;
		$this->save();
		$event = new events\transactions\Expire($this);
		$event->trigger();
	}
	/**
	 * @throws Currency\UnChangableException
	 * @return void
	 */
	public function returnPaymentsToCredit(array $methods = [Transaction_pay::credit]): void {
		$userCurrency = Currency::getDefault($this->user);
		foreach ($this->pays as $pay) {
			if ($pay->status == Transaction_Pay::accepted and in_array($pay->method, $methods)) {
				$price = $pay->currency->changeTo($pay->price, $userCurrency);
				$this->user->credit += $price;
			}
		}
		$this->user->save();
	}
	protected function addProduct($productdata){
		$product = new transaction_product($productdata);
		if ($this->isNew){
			$this->tmproduct[] = $product;
			return true;
		}else{
			$product->transaction = $this->id;
			return $product->save();
		}
	}
	protected function addPay($paydata){
		$pay = new transaction_pay($paydata);
		if ($this->isNew){
			$this->tmpays[] = $pay;
			return true;
		}else{
			$pay->transaction = $this->id;
			$pay->save();
			return $pay->id;
		}
	}
	protected function payablePrice(){
		$payable = $this->totalPrice();
		if(!$this->isNew){
			unset($this->data['pays']);
			foreach($this->pays as $pay){
				if($pay->status == transaction_pay::accepted){
					$payable -= $pay->convertPrice();
				}
			}
		}
		return $payable;
	}
	protected function trigger_paid(){
		if(!$this->param("trigered_paid")){
			$this->setParam("trigered_paid", true);
			foreach($this->products as $product){
				if($product->type and class_exists($product->type)){
					$obj = new $product->type($product->data);
					if(method_exists($obj, 'trigger_paid')){
						$obj->trigger_paid();
					}
					unset($obj);
				}
			}
		}
	}
	public function isConfigured(){
		foreach($this->products as $product){
			if(!$product->configure){
				return false;
			}
		}
		return true;
	}
	protected function preLoad($data){
		if(!isset($data['status'])){
			$data['status'] = self::unpaid;
		}
		if(!isset($data['create_at']) or !$data['create_at']){
			$data['create_at'] = time();
		}
		if(!isset($data['currency'])){
			$user = null;
			if (isset($data['user'])) {
				if (!($data['user'] instanceof dbObject)) {
					$user = user::where('id', $data['user'])->getOne();
				} else {
					$user = $data['user'];
				}
			}
			$data['currency'] = currency::getDefault($user);
		}
		if($data['currency'] instanceof dbObject){
			$data['currency'] = $data['currency']->id;
		}
		$products = array();
		if ($this->isNew){
			$products = &$this->tmproduct;
		}else{
			$products = $this->products;
		}
		$data['price'] = 0;
		foreach($products as $product){
			$price = $product->price;
			$discount = $product->discount;
			if(!$product->currency){
				if(!$data['user'] instanceof dbObject){
					$user = user::where('id', $data['user'])->getOne();
				}else{
					$user = $data['user'];
				}
				$product->currency = currency::getDefault($user);
			}
			if($data['currency'] != $product->currency->id){
				$rate = new currency\rate();
				$rate->where('currency', $product->currency->id);
				$rate->where('changeTo', $data['currency']);
				if(!$rate = $rate->getOne()){
					throw new currency\UnChangableException($product->currency, $data['currency']);
				}
				$price *= $rate->price;
				$discount *= $rate->price;
			}
			$data['price'] += (($price*$product->number) - $discount);
		}
		if (!isset($data["token"])) {
			$data["token"] = transaction::generateToken();
		}
		return $data;
	}
	protected $tmparams = array();
	public function setParam($name, $value){
		$param = false;
		foreach($this->params as $p){
			if($p->name == $name){
				$param = $p;
				break;
			}
		}
		if(!$param){
			$param = new transaction_param(array(
				'name' => $name,
				'value' => $value
			));
		}else{
			$param->value = $value;
		}

		if(!$this->id){
			$this->tmparams[$name] = $param;
		}else{
			$param->transaction = $this->id;
			return $param->save();
		}
	}
	public function param($name){
		if(!$this->id){
			return(isset($this->tmparams[$name]) ? $this->tmparams[$name]->value : null);
		}else{
			foreach($this->params as $param){
				if($param->name == $name){
					return $param->value;
				}
			}
			return false;
		}
	}
	public function save($data = null){
		if(($return = parent::save($data))){
			foreach($this->tmproduct as $product){
				$product->transaction = $this->id;
				$product->save();
			}
			$this->tmproduct = array();
			foreach($this->tmparams as $param){
				$param->transaction = $this->id;
				$param->save();
			}
			$this->tmparams = array();
			foreach($this->tmpays as $pay){
				$pay->transaction = $this->id;
				$pay->save();
			}
			$this->tmpays = array();
		}
		return $return;
	}
	public function deleteParam(string $name):bool{
		if(!$this->id){
			if(isset($this->tmparams[$name])){
				unset($this->tmparams[$name]);
			}
		}else{
			$param = new transaction_param();
			$param->where('transaction', $this->id);
			$param->where('name', $name);
			if($param = $param->getOne()){
				return $param->delete();
			}
		}
		return true;
	}
	public function afterPay(){
		$dakhlPackage = packages::package("dakhl");
		$dakhl = false;
		$invoice = false;
		$dcurrency = false;
		$pays = array();
		if ($dakhlPackage) {
			$pay = new transaction_pay();
			$pay->where("transaction", $this->id);
			$pay->where("status", transaction_pay::accepted);
			$pay->where("method", array(transaction_pay::onlinepay, transaction_pay::banktransfer), "in");
			$pays = $pay->get();
			if ($pays) {
				$ocurrency = options::get("packages.dakhl.currency");
				if (!$dcurrency = currency::where("id", $ocurrency)->getOne()) {
					throw new \Exception("notfound dakhl currency");
				}
				$dakhl = new dakhl();
				$price = $this->price;
				if ($this->currency->id != $dcurrency->id) {
					$price = $this->currency->changeTo($this->price, $dcurrency);
				}
				$invoice = $dakhl->addIncomeInvoice($this->title, $this->user, $price);
			}
		}
		$currency = $this->currency;
		foreach ($this->products as $product) {
			$pcurrency = $product->currency;
			if ($pcurrency->id != $currency->id) {
				$rate = new currency\rate();
				$rate->where('currency', $pcurrency->id);
				$rate->where('changeTo', $currency->id);
				$rate = $rate->getOne();
				if ($rate) {
					$product->price *= $rate->price;
					$product->discount *= $rate->price;
					$product->currency = $currency->id;
					$product->save();
				}
			}
			if ($invoice) {
				if (!$product->description) {
					$product->description = "";
				}
				$price = $product->price;
				$discount = $product->discount;
				if ($product->currency->id != $dcurrency->id) {
					$price = $product->currency->changeTo($product->price, $dcurrency);
					$discount = $product->currency->changeTo($product->discount, $dcurrency);
				}
				$dakhl->addInvoiceProduct($invoice, $product->title, $product->number, $price, $discount, $product->description);
			}
		}
		if ($invoice) {
			foreach ($pays as $pay) {
				$account = null;
				$description = "";
				if ($pay->method == transaction_pay::onlinepay) {
					$payparam = $pay->param("payport_pay");
					if ($payparam) {
						$payportpay = payport_pay::where("id", $payparam)->getOne();
						if ($payportpay) {
							$payport = $payportpay->payport;
							$account = $payport->account;
							$description = translator::trans("financial.pay.online", array("payport" => $payport->title));
						}
					}
				} else if ($pay->method == transaction_pay::banktransfer) {
					$payparam = $pay->param("bankaccount");
					if ($payparam) {
						$account = (new Bank\Account)->byID($payparam);
						$description = t("financial.pay.bankTransfer");
						$followup = $pay->param("followup");
						if ($followup) {
							$description .= " - " . translator::trans("financial.pay.bankTransfer.followup", array("followup" => $pay->param("followup")));
						}
					}
				}
				if (!$account) {
					continue;
				}
				$dakhlaccount = $dakhl->getBankAccount($account->bank->title, $account->shaba);
				$price = $pay->price;
				if ($pay->currency->id != $dcurrency->id) {
					$price = $pay->currency->changeTo($pay->price, $dcurrency);
				}
				$dakhl->addInvoicePay($invoice, $dakhlaccount, $price, $pay->date, $description);
			}
		}
	}
	public function totalPrice(): float{
		$currency = $this->currency;
		$price = 0;
		foreach($this->products as $product){
			$pcurrency = $product->currency;
			if($pcurrency->id != $currency->id){
				$price += $pcurrency->changeTo(($product->price * $product->number) - $product->discount, $currency);
			}else{
				$price += ($product->price * $product->number) - $product->discount;
			}
		}
		return $price;
	}
}
class undefinedCurrencyException extends \Exception{}
