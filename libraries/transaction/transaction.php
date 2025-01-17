<?php
namespace packages\financial;

use packages\userpanel\{user, date};
use packages\base\{db\dbObject, Options, Packages, Utility\Safe, Translator, db};
use packages\userpanel\CursorPaginateTrait;

/**
 * @property int $id
 * @property User $user
 * @property Currency $currency
 */
class transaction extends dbObject
{
	use CursorPaginateTrait;

	/** status */
	const UNPAID = self::unpaid;
	const PENDING = self::pending;
	const PAID = self::paid;
	const REFUND = self::refund;
	const EXPIRED = self::expired;
	const REJECTED = self::rejected;

	/** old style const, we dont removed these for backward compatibility */
	const unpaid = 1;
	const pending = 6;
	const paid = 2;
	const refund = 3;
	const expired = 4;
	const rejected = 5;

	const ONLINE_PAYMENT_METHOD = 'onlinepay';
	const BANK_TRANSFER_PAYMENT_METHOD = 'banktransfer';
	const CREDIT_PAYMENT_METHOD = 'credit';

	public static function generateToken(int $length = 15): string {
		$numberChar = "0123456789";
		$pw = "";
		while (strlen($pw) < $length) {
			$pw .= substr($numberChar, rand(0, 9), 1);
		}
		return $pw;
	}

	public static function canCreateCheckoutTransaction(int $userID, ?float $price = null): bool
	{
		$limits = self::getCheckoutLimits($userID);

		if (!$limits or !isset($limits['price']) or !isset($limits['currency']) or !isset($limits['period'])) {
			return true;
		}

		$query = new User();
		$user = $query->byId($userID);
		if (!$user) {
			return false;
		}

		$lastCheckoutAt = $user->option('financial_last_checkout_time');

		if ($lastCheckoutAt and (Date::time() - $lastCheckoutAt) < $limits['period']) {
			return false;
		}

		if ($price and Safe::floats_cmp($limits['price'], $price) > 0) {
			return false;
		}

		return true;
	}

	public static function getCheckoutLimits(?int $userID = null): array
	{
		$limits = [];
		$user = null;
		if ($userID) {
			$query = new User();
			$user = $query->byId($userID);

			if ($user) {
				$limits = $user->option('financial_checkout_limits');
			}
		}

		if (!$limits) {
			$limits = Options::get('packages.financial.checkout_limits') ?: [];
		}

		if (isset($limits['currency'])) {
			$query = new Currency();
			$limits['currency'] = $query->byId($limits['currency']);

			if (!$limits['currency']) {
				unset($limits['currency']);
			}
		}

		if ($user and isset($limits['currency'], $limits['price'])) {
			$currency = Currency::getDefault($user);
			$limits['price'] = $limits['currency']->changeTo($limits['price'], $currency);
		}

		return $limits;
	}
	private static function getVatConfig(): array {

		$options = Options::get("packages.financial.vat_config");

		if (!$options) {
			return [];
		}

		if (!isset($options["exclude-products"]) or !$options["exclude-products"]) {
			$options["exclude-products"] = [];
		}

		if (!is_array($options["exclude-products"])) {
			$options["exclude-products"] = [$options["exclude-products"]];
		}

		if (!isset($options["products"]) or !$options["products"]) {
			$options["products"] = [];
		}

		if (!is_array($options["products"])) {
			$options["products"] = [$options["products"]];
		}

		$options["exclude-products"] = array_merge($options["exclude-products"], [products\AddingCredit::class, "\\" . products\AddingCredit::class]);

		$options["exclude-products"] = array_map("strtolower", $options["exclude-products"]);

		$options["default_vat"] = $options["default_vat"] ?? 9;

		if (!isset($options["users"]) or !$options["users"]) {
			$options["users"] = [];
		}

		if (!is_array($options["users"])) {
			$options["users"] = [$options["users"]];
		}

		$products = [];

		foreach ($options["products"] as $key => $value) {
			if (is_string($key)) {
				$products[strtolower($key)] = $value;
			} elseif (is_string($value)) {
				$products[strtolower($value)] = $options["default_vat"];
			}
		}

		$options["products"] = $products;

		return $options;
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
	private ?TransactionManager $transactionManager = null;
	
	public function expire() {
		$this->byId($this->id);
		if ($this->status != self::UNPAID) {
			return;
		}
		$totalPrice = $this->getTotalPrice();
		if ($totalPrice < 0) {
			$payablePrice = abs($totalPrice);

			$userCurrency = Currency::getDefault($this->user);
			$price = $this->currency->changeTo($payablePrice, $userCurrency);

			db::where("id", $this->user->id)
				->update("userpanel_users", array(
					"credit" => db::inc($price),
				));
		} else {
			$this->returnPaymentsToCredit();
		}

		$this->status = self::expired;
		$this->save();

		$event = new events\transactions\Expire($this);
		$event->trigger();
	}
	/**
	 * get total price of products based on transaction currency
	 *
	 * @return float
	 */
	public function totalPrice(): float {
		return $this->getTotalPrice();
	}

	/**
	 * @throws Currency\UnChangableException
	 * @return void
	 */
	public function returnPaymentsToCredit(): void
	{
		$userCurrency = Currency::getDefault($this->user);
		$methods = array_keys($this->getTransactionManager()->getPaymentMethods($this));
		$total = 0;
		foreach ($this->pays as $pay) {
			if ($pay->status == Transaction_Pay::accepted and in_array($pay->method, $methods)) {
				$total += $pay->currency->changeTo($pay->price, $userCurrency);
			}
		}
		db::where("id", $this->user->id)
			->update("userpanel_users", array(
				"credit" => db::inc($total),
			));
	}
	public function canAddPay(): bool {
		if (!in_array($this->status, [self::UNPAID, self::PENDING])) {
			return false;
		}

		return $this->getTotalPrice() > 0 and ($this->remainPriceForAddPay() > 0 or $this->getTransactionManager()->canOverPay($this));
	}
	public function remainPriceForAddPay(): float {
		$remainPrice = $this->totalPrice();
		unset($this->data["pays"]);
		foreach ($this->pays as $pay) {
			if (in_array($pay->status, [Transaction_pay::ACCEPTED, Transaction_pay::PENDING])) {
				$remainPrice -= $pay->convertPrice();
			}
		}
		return $remainPrice;
	}
	public function canPayByCredit(): bool {
		return !((new Transaction_Product())
		->where("transaction", $this->id)
		->where("type", [products\AddingCredit::class, "\\" . products\AddingCredit::class], "IN")
		->has());
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
	protected function payablePrice(): float {
		$payable = $this->totalPrice();
		$paid = 0;
		if (!$this->isNew) {
			unset($this->data['pays']);
			foreach ($this->pays as $pay) {
				if ($pay->status == transaction_pay::accepted) {
					$paid += $pay->convertPrice();
				}
			}
		}
		return (Safe::floats_cmp($payable, $paid) == 0 ? 0 : floatval($payable - $paid));
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
	protected function preLoad(array $data): array {
		if(!isset($data['status'])){
			$data['status'] = self::unpaid;
		}
		if(!isset($data['create_at']) or !$data['create_at']){
			$data['create_at'] = time();
		}
		$userModel = null;
		if (isset($data['user'])) {
			$userModel = (($data['user'] instanceof dbObject) ? $data['user'] : (new User())->byID($data['user']));
		}

		if (!isset($data['currency'])) {
			$this->currency = $data['currency'] = Currency::getDefault($userModel);
		}

		$products = array();

		if ($this->isNew) {
			$products = &$this->tmproduct;
		} else {
			$products = $this->products;
		}

		foreach ($products as $product) {
			if (!$product->currency) {
				$product->currency = $data['currency'];
			}
		}

		if ($userModel) {
			$options = self::getVatConfig();

			if ($options) {

				if (!isset($options["users"][$userModel->id]) and in_array($userModel->id, $options["users"])) {
					$options["users"][$userModel->id] = $options["default_vat"];
				}

				if (isset($options["users"][$userModel->id])) {
					foreach ($products as $product) {
						if (in_array(strtolower($product->type), $options["exclude-products"]) or $product->vat) {
							continue;
						}
						$product->vat = $options["users"][$userModel->id];
					}
				} elseif ($options["products"]) {

					foreach ($products as $product) {

						$type = strtolower($product->type);

						if (in_array($type, $options["exclude-products"]) or !isset($options["products"][$type]) or $product->vat) {
							continue;
						}

						$product->vat = $options["products"][$type];
					}
				}
			}
		}

		$data['price'] = $this->getTotalPrice();

		if (!isset($data["token"])) {
			$data["token"] = transaction::generateToken();
		}

		if ($data['currency'] instanceof dbObject) {
			$data['currency'] = $data['currency']->id;
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
		$pays = array();
		$currency = $this->currency;
		foreach ($this->products as $product) {
			$pcurrency = $product->currency;
			try {
				$product->price = $pcurrency->changeTo($product->price, $currency);
				$product->discount = $pcurrency->changeTo($product->discount, $currency);
				$product->currency = $currency->id;
				$product->save();
			} catch (currency\UnChangableException $e) {}
		}
	}

	public function getVat(): float
	{
		return array_sum(array_map(fn (Transaction_product $product) => $product->getVat($this->currency), $this->products));
	}

	public function getPrice(): float
	{
		return array_sum(array_map(fn (Transaction_product $product) => $product->getPrice($this->currency), $this->products));
	}

	public function getDiscount(): float
	{
		return array_sum(array_map(fn (Transaction_product $product) => $product->getDiscount($this->currency), $this->products));
	}

	public function getTotalPrice(): float
	{
		return array_sum(array_map(fn (Transaction_product $product) => $product->totalPrice($this->currency), $this->isNew ? $this->tmproduct : $this->products));
	}

	private function getTransactionManager(): TransactionManager
	{
		return $this->transactionManager ?: $this->transactionManager = TransactionManager::getInstance();
	}
}
class undefinedCurrencyException extends \Exception{}
