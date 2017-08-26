<?php
namespace packages\financial\controllers\settings;
use \packages\base\NotFound;
use \packages\base\db\parenthesis;
use \packages\base\views\FormError;
use \packages\base\inputValidation;
use \packages\base\db\duplicateRecord;

use \packages\userpanel;
use \packages\financial\view;
use \packages\financial\usertype;
use \packages\financial\controller;
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
			]
		];
		try{
			$inputs = $this->checkinputs($inputsRules);
			$currency = new currency();
			$currency->where('title', $inputs['title']);
			if($currency->has()){
				throw new duplicateRecord('title');
			}
			if(!isset($inputs['change']) or $inputs['change']){
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
				'type' => 'string'
			],
			'change' => [
				'type' => 'bool',
				'optional' => true
			],
			'rates' => [
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
			$currency->title = $inputs['title'];
			if(isset($inputs['rates'])){
				$ids = $inputs['ids'];
				foreach($currency->rates as $rate){
					if(!in_array($rate->id, $ids)){
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
			$currency->delete();
			$this->response->Go(userpanel\url('settings/financial/currencies'));
		}catch(inputValidation $error){
			$this->response->setStatus(false);
			$view->setFormError(FormError::fromException($error));
		}
		$view->setDataForm($this->inputsvalue($inputsRules));
		$this->response->setView($view);
		return $this->response;
	}
}
