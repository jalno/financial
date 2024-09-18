<?php

namespace packages\financial;

use packages\base\DB;
use packages\base\DB\DBObject;
use packages\base\Options;
use packages\base\Packages;
use packages\base\Translator;
use packages\base\Utility\Safe;
use packages\dakhl\API as Dakhl;
use packages\userpanel\Date;
use packages\userpanel\User;

class Transaction extends DBObject
{
    /** status */
    public const UNPAID = self::unpaid;
    public const PENDING = self::pending;
    public const PAID = self::paid;
    public const REFUND = self::refund;
    public const EXPIRED = self::expired;
    public const REJECTED = self::rejected;

    /** old style const, we dont removed these for backward compatibility */
    public const unpaid = 1;
    public const pending = 6;
    public const paid = 2;
    public const refund = 3;
    public const expired = 4;
    public const rejected = 5;

    public const ONLINE_PAYMENT_METHOD = 'onlinepay';
    public const BANK_TRANSFER_PAYMENT_METHOD = 'banktransfer';
    public const CREDIT_PAYMENT_METHOD = 'credit';

    public static function generateToken(int $length = 15): string
    {
        $numberChar = '0123456789';
        $pw = '';
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

    private static function getVatConfig(): array
    {
        $options = Options::get('packages.financial.vat_config');

        if (!$options) {
            return [];
        }

        if (!isset($options['exclude-products']) or !$options['exclude-products']) {
            $options['exclude-products'] = [];
        }

        if (!is_array($options['exclude-products'])) {
            $options['exclude-products'] = [$options['exclude-products']];
        }

        if (!isset($options['products']) or !$options['products']) {
            $options['products'] = [];
        }

        if (!is_array($options['products'])) {
            $options['products'] = [$options['products']];
        }

        $options['exclude-products'] = array_merge($options['exclude-products'], [Products\AddingCredit::class, '\\'.Products\AddingCredit::class]);

        $options['exclude-products'] = array_map('strtolower', $options['exclude-products']);

        $options['default_vat'] = $options['default_vat'] ?? 9;

        if (!isset($options['users']) or !$options['users']) {
            $options['users'] = [];
        }

        if (!is_array($options['users'])) {
            $options['users'] = [$options['users']];
        }

        $products = [];

        foreach ($options['products'] as $key => $value) {
            if (is_string($key)) {
                $products[strtolower($key)] = $value;
            } elseif (is_string($value)) {
                $products[strtolower($value)] = $options['default_vat'];
            }
        }

        $options['products'] = $products;

        return $options;
    }

    protected $dbTable = 'financial_transactions';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'id' => ['type' => 'int'],
        'token' => ['type' => 'text', 'required' => true, 'unique' => true],
        'user' => ['type' => 'int'],
        'title' => ['type' => 'text', 'required' => true],
        'price' => ['type' => 'double', 'required' => true],
        'create_at' => ['type' => 'int', 'required' => true],
        'expire_at' => ['type' => 'int'],
        'paid_at' => ['type' => 'int'],
        'currency' => ['type' => 'int', 'required' => true],
        'status' => ['type' => 'int', 'required' => true],
    ];
    protected $relations = [
        'user' => ['hasOne', User::class, 'user'],
        'currency' => ['hasOne', Currency::class, 'currency'],
        'params' => ['hasMany', TransactionParam::class, 'transaction'],
        'products' => ['hasMany', TransactionProduct::class, 'transaction'],
        'pays' => ['hasMany', TransactionPay::class, 'transaction'],
    ];
    protected $tmproduct = [];
    protected $tmpays = [];

    public function expire()
    {
        $this->byId($this->id);
        if (self::UNPAID != $this->status) {
            return;
        }
        $payablePrice = $this->payablePrice();
        if ($payablePrice < 0) {
            $payablePrice = abs($payablePrice);

            $userCurrency = Currency::getDefault($this->user);
            $price = $this->currency->changeTo($payablePrice, $userCurrency);

            DB::where('id', $this->user->id)
                ->update('userpanel_users', [
                    'credit' => DB::inc($price),
                ]);
        } else {
            $this->returnPaymentsToCredit([TransactionPay::credit, TransactionPay::onlinepay, TransactionPay::banktransfer]);
        }

        $this->status = self::expired;
        $this->save();

        $event = new Events\Transactions\Expire($this);
        $event->trigger();
    }

    /**
     * get total price of products based on transaction currency.
     */
    public function totalPrice(): float
    {
        return $this->getTotalPrice();
    }

    /**
     * @throws Currency\UnChangableException
     */
    public function returnPaymentsToCredit(array $methods = [TransactionPay::credit]): void
    {
        $userCurrency = Currency::getDefault($this->user);
        $total = 0;
        foreach ($this->pays as $pay) {
            if (TransactionPay::accepted == $pay->status and in_array($pay->method, $methods)) {
                $total += $pay->currency->changeTo($pay->price, $userCurrency);
            }
        }
        DB::where('id', $this->user->id)
            ->update('userpanel_users', [
                'credit' => DB::inc($total),
            ]);
    }

    public function canAddPay(): bool
    {
        if (!in_array($this->status, [self::UNPAID, self::PENDING])) {
            return false;
        }

        return 0 != $this->remainPriceForAddPay();
    }

    public function remainPriceForAddPay(): float
    {
        $remainPrice = $this->totalPrice();
        unset($this->data['pays']);
        foreach ($this->pays as $pay) {
            if (in_array($pay->status, [TransactionPay::ACCEPTED, TransactionPay::PENDING])) {
                $remainPrice -= $pay->convertPrice();
            }
        }

        return $remainPrice;
    }

    public function canPayByCredit(): bool
    {
        return !(new TransactionProduct())
        ->where('transaction', $this->id)
        ->where('type', [Products\AddingCredit::class, '\\'.Products\AddingCredit::class], 'IN')
        ->has();
    }

    protected function addProduct($productdata)
    {
        $product = new TransactionProduct($productdata);
        if ($this->isNew) {
            $this->tmproduct[] = $product;

            return true;
        } else {
            $product->transaction = $this->id;

            return $product->save();
        }
    }

    protected function addPay($paydata)
    {
        $pay = new TransactionPay($paydata);
        if ($this->isNew) {
            $this->tmpays[] = $pay;

            return true;
        } else {
            $pay->transaction = $this->id;
            $pay->save();

            return $pay->id;
        }
    }

    protected function payablePrice(): float
    {
        $payable = $this->totalPrice();
        $paid = 0;
        if (!$this->isNew) {
            unset($this->data['pays']);
            foreach ($this->pays as $pay) {
                if (TransactionPay::accepted == $pay->status) {
                    $paid += $pay->convertPrice();
                }
            }
        }

        return 0 == Safe::floats_cmp($payable, $paid) ? 0 : floatval($payable - $paid);
    }

    protected function trigger_paid()
    {
        if (!$this->param('trigered_paid')) {
            $this->setParam('trigered_paid', true);
            foreach ($this->products as $product) {
                if ($product->type and class_exists($product->type)) {
                    $obj = new $product->type($product->data);
                    if (method_exists($obj, 'trigger_paid')) {
                        $obj->trigger_paid();
                    }
                    unset($obj);
                }
            }
        }
    }

    public function isConfigured()
    {
        foreach ($this->products as $product) {
            if (!$product->configure) {
                return false;
            }
        }

        return true;
    }

    protected function preLoad(array $data): array
    {
        if (!isset($data['status'])) {
            $data['status'] = self::unpaid;
        }
        if (!isset($data['create_at']) or !$data['create_at']) {
            $data['create_at'] = time();
        }
        $userModel = null;
        if (isset($data['user'])) {
            $userModel = (($data['user'] instanceof DBObject) ? $data['user'] : (new User())->byID($data['user']));
        }

        if (!isset($data['currency'])) {
            $this->currency = $data['currency'] = Currency::getDefault($userModel);
        }

        $products = [];

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
                if (!isset($options['users'][$userModel->id]) and in_array($userModel->id, $options['users'])) {
                    $options['users'][$userModel->id] = $options['default_vat'];
                }

                if (isset($options['users'][$userModel->id])) {
                    foreach ($products as $product) {
                        if (in_array(strtolower($product->type), $options['exclude-products']) or $product->vat) {
                            continue;
                        }
                        $product->vat = $options['users'][$userModel->id];
                    }
                } elseif ($options['products']) {
                    foreach ($products as $product) {
                        $type = strtolower($product->type);

                        if (in_array($type, $options['exclude-products']) or !isset($options['products'][$type]) or $product->vat) {
                            continue;
                        }

                        $product->vat = $options['products'][$type];
                    }
                }
            }
        }

        $data['price'] = $this->getTotalPrice();

        if (!isset($data['token'])) {
            $data['token'] = Transaction::generateToken();
        }

        if ($data['currency'] instanceof DBObject) {
            $data['currency'] = $data['currency']->id;
        }

        return $data;
    }
    protected $tmparams = [];

    public function setParam($name, $value)
    {
        $param = false;
        foreach ($this->params as $p) {
            if ($p->name == $name) {
                $param = $p;
                break;
            }
        }
        if (!$param) {
            $param = new TransactionParam([
                'name' => $name,
                'value' => $value,
            ]);
        } else {
            $param->value = $value;
        }

        if (!$this->id) {
            $this->tmparams[$name] = $param;
        } else {
            $param->transaction = $this->id;

            return $param->save();
        }
    }

    public function param($name)
    {
        if (!$this->id) {
            return isset($this->tmparams[$name]) ? $this->tmparams[$name]->value : null;
        } else {
            foreach ($this->params as $param) {
                if ($param->name == $name) {
                    return $param->value;
                }
            }

            return false;
        }
    }

    public function save($data = null)
    {
        if ($return = parent::save($data)) {
            foreach ($this->tmproduct as $product) {
                $product->transaction = $this->id;
                $product->save();
            }
            $this->tmproduct = [];
            foreach ($this->tmparams as $param) {
                $param->transaction = $this->id;
                $param->save();
            }
            $this->tmparams = [];
            foreach ($this->tmpays as $pay) {
                $pay->transaction = $this->id;
                $pay->save();
            }
            $this->tmpays = [];
        }

        return $return;
    }

    public function deleteParam(string $name): bool
    {
        if (!$this->id) {
            if (isset($this->tmparams[$name])) {
                unset($this->tmparams[$name]);
            }
        } else {
            $param = new TransactionParam();
            $param->where('transaction', $this->id);
            $param->where('name', $name);
            if ($param = $param->getOne()) {
                return $param->delete();
            }
        }

        return true;
    }

    public function afterPay()
    {
        $dakhlPackage = Packages::package('dakhl');
        $dakhl = false;
        $invoice = false;
        $dcurrency = false;
        $pays = [];
        if ($dakhlPackage and class_exists(Dakhl::class)) {
            $pay = new TransactionPay();
            $pay->where('transaction', $this->id);
            $pay->where('status', TransactionPay::accepted);
            $pay->where('method', [TransactionPay::onlinepay, TransactionPay::banktransfer], 'in');
            $pays = $pay->get();
            if ($pays) {
                $ocurrency = Options::get('packages.dakhl.currency');
                if (!$dcurrency = Currency::where('id', $ocurrency)->getOne()) {
                    throw new \Exception('notfound dakhl currency');
                }
                $dakhl = new Dakhl();
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
            try {
                $product->price = $pcurrency->changeTo($product->price, $currency);
                $product->discount = $pcurrency->changeTo($product->discount, $currency);
                $product->currency = $currency->id;
                $product->save();
            } catch (Currency\UnChangableException $e) {
            }
            if ($invoice) {
                if (!$product->description) {
                    $product->description = '';
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
                $description = '';
                if (TransactionPay::onlinepay == $pay->method) {
                    $payparam = $pay->param('payport_pay');
                    if ($payparam) {
                        $payportpay = PayPortPay::where('id', $payparam)->getOne();
                        if ($payportpay) {
                            $payport = $payportpay->payport;
                            $account = $payport->account;
                            $description = t('financial.pay.online', ['payport' => $payport->title]);
                        }
                    }
                } elseif (TransactionPay::banktransfer == $pay->method) {
                    $payparam = $pay->param('bankaccount');
                    if ($payparam) {
                        $account = (new Bank\Account())->byID($payparam);
                        $description = t('financial.pay.bankTransfer');
                        $followup = $pay->param('followup');
                        if ($followup) {
                            $description .= ' - '.t('financial.pay.bankTransfer.followup', ['followup' => $pay->param('followup')]);
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

    public function getVat(): float
    {
        return array_sum(array_map(fn (TransactionProduct $product) => $product->getVat($this->currency), $this->products));
    }

    public function getPrice(): float
    {
        return array_sum(array_map(fn (TransactionProduct $product) => $product->getPrice($this->currency), $this->products));
    }

    public function getDiscount(): float
    {
        return array_sum(array_map(fn (TransactionProduct $product) => $product->getDiscount($this->currency), $this->products));
    }

    public function getTotalPrice(): float
    {
        return array_sum(array_map(fn (TransactionProduct $product) => $product->totalPrice($this->currency), $this->isNew ? $this->tmproduct : $this->products));
    }
}

