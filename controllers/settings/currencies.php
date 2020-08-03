<?php
namespace packages\financial\controllers\settings;

use packages\base\{db, InputValidation, InputValidationException, NotFound, Response, Translator, Validator};
use packages\base\{db\DuplicateRecord, view\Error, views\FormError, db\Parenthesis,};
use packages\financial\{Authentication, Authorization, Controller, Currency, Transaction, Usertype, View};
use packages\financial\views\settings\Currencies as CurrencyView;
use packages\userpanel;
use packages\userpanel\{Date};

class Currencies extends Controller {
	protected $authentication = true;

	public function search(){
		authorization::haveOrFail('settings_currencies_search');
		$view = view::byName(currencyview\search::class);

		$currency = new currency();
		$inputsRules = [
			'id' => [
				'type' => 'number',
				'optional' => true,
				'empty' => true,
			],
			'title' => [
				'type' => 'string',
				'optional' => true,
				'empty' => true,
			],
			'word' => [
				'type' => 'string',
				'optional' => true,
				'empty' => true
			],
			'comparison' => [
				'values' => ['equals', 'startswith', 'contains'],
				'default' => 'contains',
				'optional' => true
			]
		];
		try{
			$inputs = $this->checkinputs($inputsRules);

			foreach(['id', 'title'] as $item){
				if(isset($inputs[$item]) and $inputs[$item]){
					$comparison = $inputs['comparison'];
					if(in_array($item, ['id'])){
						$comparison = 'equals';
					}
					$currency->where($item, $inputs[$item], $comparison);
				}
			}
			if(isset($inputs['word']) and $inputs['word']){
				$parenthesis = new parenthesis();
				foreach(['title'] as $item){
					if(!isset($inputs[$item]) or !$inputs[$item]){
						$parenthesis->where($item, $inputs['word'], $inputs['comparison'], 'OR');
					}
				}
				$currency->where($parenthesis);
			}
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
		}
		$view->setDataForm($this->inputsvalue($inputsRules));

		$currencies = $currency->paginate($this->page);
		$this->total_pages = $currency->totalPages;
		$view->setDataList($currencies);
		$view->setPaginate($this->page, $currency->totalCount, $this->items_per_page);

		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function add(){
		authorization::haveOrFail('settings_currencies_add');
		$view = view::byName(currencyview\add::class);
		$view->setCurrencies(currency::get());
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function store(): Response {
		Authorization::haveOrFail('settings_currencies_add');
		$view = View::byName(CurrencyView\Add::class);
		$this->response->setView($view);
		$view->setCurrencies(Currency::get());
		$rules = [
			'title' => [
				'type' => function ($data, $rule, $input) {
					$data = (new Validator\StringValidator)->validate($input, $rule, $data);
					$isExists = (new Currency)->where('title', $data)->has();
					if ($isExists) {
						throw new DuplicateRecord('title');
					}
					return $data;
				},
			],
			'update_at' => [
				'type' => 'date',
				'unix' => true,
			],
			'change' => [
				'type' => 'bool',
				'optional' => true,
			],
			'rounding-behaviour' => [
				'type' => 'number',
				'values' => [Currency::CEIL, Currency::ROUND, Currency::FLOOR],
				'optional' => true,
			],
			'rounding-precision' => [
				'type' => 'number',
				'zero' => true,
				'optional' => true,
			],
			'rates' => [
				'type' => function ($data, $rule, $input) {
					if (!is_array($data)) {
						throw new InputValidationException($input);
					}
					foreach ($data as $key => $rate) {
						if (!isset($rate['currency']) or !isset($rate['price'])) {
							throw new InputValidationException("rates[{$key}]");
						}
						foreach ($data as $key2 => $rate2) {
							if ($key2 != $key and $rate2['currency'] == $rate['currency']) {
								throw new DuplicateRecord("rates[{$key}][currency]");
							}
						}
						$data[$key]['currency'] = Currency::byID($rate['currency']);
						if (!$data[$key]['currency']) {
							throw new InputValidationException("rates[{$key}][currency]");
						}
						if ($rate['price'] <= 0) {
							throw new InputValidationException("rates[{$key}][price]");
						}
					}
					return $data;
				},
				'optional' => true,
			],
		];
		$view->setDataForm($this->inputsValue($rules));
		$inputs = $this->checkInputs($rules);
		if ((isset($inputs['rounding-behaviour']) and $inputs['rounding-behaviour'] == Currency::ROUND) and
			(!isset($inputs['rounding-precision']) or $inputs['rounding-precision'] === null)
		) {
			throw new InputValidationException('rounding-precision');
		}
		if (!isset($inputs['change']) or !$inputs['change']) {
			unset($inputs['rates']);
			unset($inputs['rounding-behaviour']);
			unset($inputs['rounding-precision']);
		} elseif (!isset($inputs['rates'])) {
			throw new InputValidationException("rates");
		}

		$currency = new Currency();
		$currency->title = $inputs['title'];
		$currency->update_at = $inputs['update_at'];
		if (isset($inputs['rounding-behaviour'])) {
			$currency->rounding_behaviour = $inputs['rounding-behaviour'];
		}
		if (isset($inputs['rounding-precision'])) {
			$currency->rounding_precision = $inputs['rounding-precision'];
		}
		$currency->save();
		if (isset($inputs['rates'])) {
			foreach ($inputs['rates'] as $key => $rate) {
				$currency->addRate($rate['currency'], $rate['price']);
			}
		}
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("settings/financial/currencies/edit/{$currency->id}"));
		return $this->response;
	}
	public function edit($data){
		authorization::haveOrFail('settings_currencies_edit');
		$currency = currency::byId($data['currency']);
		if(!$currency){
			throw new NotFound();
		}
		$view = view::byName(currencyview\edit::class);
		$view->setCurrency($currency);
		$currencies = new currency();
		$currencies->where('id', $currency->id, "!=");
		$view->setCurrencies($currencies->get());
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function update($data){
		authorization::haveOrFail('settings_currencies_edit');
		$currency = currency::byId($data['currency']);
		if(!$currency){
			throw new NotFound();
		}
		$view = view::byName(currencyview\edit::class);
		$view->setCurrency($currency);
		$currencies = new currency();
		$currencies->where('id', $currency->id, "!=");
		$view->setCurrencies($currencies->get());
		$this->response->setStatus(false);
		$inputsRules = [
			'title' => [
				'type' => 'string',
				'optional' => true
			],
			'change' => [
				'type' => 'bool',
				'optional' => true
			],
			'rates' => [
				'optional' => true
			],
			'update_at' => [
				'type' => 'date',
				'optional' => true
			]
		];
		try{
			$inputs = $this->checkinputs($inputsRules);
			$currencyObj = new currency();
			$currencyObj->where('id', $currency->id, "!=");
			$currencyObj->where('title', $inputs['title']);
			if($currencyObj->has()){
				throw new duplicateRecord('title');
			}
			if(isset($inputs['title']) and !$inputs['title']){
				unset($inputs['title']);
			}
			if(isset($inputs['update_at'])){
				if($inputs['update_at']){
					$inputs['update_at'] = date::strtotime($inputs['update_at']);
				}else{
					unset($inputs['update_at']);
				}
			}
			if(!isset($inputs['change']) or !$inputs['change']){
				unset($inputs['rates']);
			}
			if(isset($inputs['update_at'])){
				if($inputs['update_at'] < 0){
					throw new inputValidation('update_at');
				}
			}
			if(isset($inputs['rates'])){
				if(!is_array($inputs['rates'])){
					throw new inputValidation('rates');
				}
				foreach($inputs['rates'] as $key => $rate){
					if(!isset($rate['currency']) or !isset($rate['price'])){
						throw new inputValidation("rates[{$key}]");
					}
					foreach($inputs['rates'] as $key2 => $rate2){
						if($key != $key2 and $rate2['currency'] == $rate['currency']){
							throw new duplicateRecord("rates[{$key}][currency]");
						}
					}
					if(!$rate['currency'] = currency::byId($rate['currency'])){
						throw new inputValidation("rates[{$key}][currency]");
					}
					if($rate['price'] <= 0){
						throw new inputValidation("rates[{$key}][price]");
					}
					$inputs['ids'][] = $rate['currency']->id;
					$inputs['rates'][$key]['currency'] = $rate['currency'];
				}
			}
			if(isset($inputs['title'])){
				$currency->title = $inputs['title'];
			}
			if(isset($inputs['update_at'])){
				$currency->update_at = $inputs['update_at'];
			}
			if(isset($inputs['rates'])){
				foreach($currency->rates as $rate){
					if(!in_array($rate->changeTo->id, $inputs['ids'])){
						$transaction = new transaction();
						$changeTo = $rate->changeTo;
						db::join('financial_transactions_products', 'financial_transactions_products.transaction=financial_transactions.id', "LEFT");
						$transaction->where('financial_transactions.currency', $changeTo->id);
						$transaction->where('financial_transactions.status', transaction::unpaid);
						$transaction->where('financial_transactions_products.currency', $currency->id);
						if($transaction->has()){
							throw new dependenciesChangebleRateException($changeTo);
						}
						$rate->delete();
					}
				}
				foreach($inputs['rates'] as $key => $rate){
					$currency->addRate($rate['currency'], $rate['price']);
				}
			}else{
				$currency->deleteRate();
			}
			$currency->save();
			$this->response->setStatus(true);
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
		}catch(duplicateRecord $error){
			$view->setFormError(FormError::fromException($error));
		}catch(dependenciesChangebleRateException $e){
			$error = new error();
			$error->setCode('financial.currencies.edit.dependenciesChangebleRateException');
			$error->setMessage(translator::trans('error.financial.currencies.edit.dependenciesChangebleRateException', ['currency'=> $e->getCurrency()->title]));
			$view->addError($error);
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$this->response->setView($view);
		return $this->response;
	}
	public function delete($data){
		authorization::haveOrFail('settings_currencies_delete');
		$currency = currency::byId($data['currency']);
		if(!$currency){
			throw new NotFound();
		}
		$view = view::byName(currencyview\delete::class);
		$view->setCurrency($currency);
		$this->response->setStatus(true);
		$this->response->setView($view);
		return $this->response;
	}
	public function terminate($data){
		authorization::haveOrFail('settings_currencies_delete');
		$currency = currency::byId($data['currency']);
		if(!$currency){
			throw new NotFound();
		}
		$view = view::byName(currencyview\delete::class);
		$view->setCurrency($currency);
		try{
			$this->response->setStatus(true);
			$transaction = new transaction();
			$transaction->where('currency', $currency->id);
			if($transaction->has()){
				throw new dependenciesTransactionException();
			}
			$userCurrency = $user->option('financial_transaction_currency');
			if($userCurrency and $userCurrency == $currency->id){
				throw new dependenciesUserCurrencyException();
			}
			$currency->delete();
			$this->response->Go(userpanel\url('settings/financial/currencies'));
		}catch(dependenciesTransactionException $e){
			$this->response->setStatus(false);
			$error = new error();
			$error->setCode('financial.currencies.terminate.dependenciesTransactionException');
			$view->addError($error);
		}catch(dependenciesTransactionException $e){
			$this->response->setStatus(false);
			$error = new error();
			$error->setCode('financial.currencies.terminate.dependenciesUserCurrencyException');
			$view->addError($error);
		}catch(inputValidation $error){
			$this->response->setStatus(false);
			$view->setFormError(FormError::fromException($error));
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$this->response->setView($view);
		return $this->response;
	}
}
class dependenciesTransactionException extends \Exception{}
class dependenciesUserCurrencyException extends \Exception{}
class dependenciesChangebleRateException extends \Exception{
	private $currency;
	public function __construct(currency $currency){
		$this->currency = $currency;
	}
	public function getCurrency():currency{
		return $this->currency;
	}
}
