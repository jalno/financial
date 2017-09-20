<?php
namespace packages\financial\controllers\settings;
use \packages\base\db;
use \packages\base\NotFound;
use \packages\base\translator;
use \packages\base\view\error;
use \packages\base\db\parenthesis;
use \packages\base\views\FormError;
use \packages\base\inputValidation;
use \packages\base\db\duplicateRecord;

use \packages\userpanel;
use \packages\userpanel\date;
use \packages\financial\view;
use \packages\financial\usertype;
use \packages\financial\controller;
use \packages\financial\transaction;
use \packages\financial\authorization;
use \packages\financial\authentication;
use \packages\financial\currency;
use \packages\financial\views\settings\currencies as currencyview;
class currencies extends controller{
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
	public function store(){
		authorization::haveOrFail('settings_currencies_add');
		$view = view::byName(currencyview\add::class);
		$view->setCurrencies(currency::get());
		$this->response->setStatus(false);
		$inputsRules = [
			'title' => [
				'type' => 'string'
			],
			'change' => [
				'type' => 'bool',
				'optional' => true
			],
			'rates' => [
				'optional' => true
			],
			'update_at' => [
				'type' => 'date'
			]
		];
		try{
			$inputs = $this->checkinputs($inputsRules);
			$currency = new currency();
			$currency->where('title', $inputs['title']);
			if($currency->has()){
				throw new duplicateRecord('title');
			}
			$inputs['update_at'] = date::strtotime($inputs['update_at']);
			if($inputs['update_at'] < 0){
				throw new inputValidation('update_at');
			}
			if(!isset($inputs['change']) or !$inputs['change']){
				unset($inputs['rates']);	
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
						if($key2 != $key and $rate2['currency'] == $rate['currency']){
							throw new duplicateRecord("rates[{$key}][currency]");
						}
					}
					if(!$inputs['rates'][$key]['currency'] = currency::byId($rate['currency'])){
						throw new inputValidation("rates[{$key}][currency]");
					}
					if($rate['price'] <= 0){
						throw new inputValidation("rates[{$key}][price]");
					}
				}
			}
			$currency = new currency();
			$currency->title = $inputs['title'];
			$currency->update_at = $inputs['update_at'];
			$currency->save();
			if(isset($inputs['rates'])){
				foreach($inputs['rates'] as $key => $rate){
					$currency->addRate($rate['currency'], $rate['price']);
				}
			}
			$this->response->setStatus(true);
			$this->response->Go(userpanel\url("settings/financial/currencies/edit/{$currency->id}"));
		}catch(inputValidation $error){
			$view->setFormError(FormError::fromException($error));
		}catch(duplicateRecord $error){
			$view->setFormError(FormError::fromException($error));
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$this->response->setView($view);
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
