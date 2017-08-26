<?php
namespace themes\clipone\views\financial\settings\currencies;
use \packages\base\translator;
use \packages\base\view\error;
use \packages\userpanel;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation\menuItem;
use \packages\financial\views\settings\currencies\search as currenciesListview;
class search extends currenciesListview{
	use viewTrait, listTrait, formTrait;
	function __beforeLoad(){
		$this->setTitle(translator::trans("settings.financial.currencies"));
		navigation::active("settings/financial/currencies");
		$this->setButtons();
		if(empty($this->getDataList())){
			$this->addNotFoundError();
		}
	}
	private function addNotFoundError(){
		$error = new error();
		$error->setType(error::NOTICE);
		$error->setCode('financial.settings.currency.notfound');
		if($this->canAdd){
			$error->setData([
				[
					'type' => 'btn-success',
					'txt' => translator::trans('settings.financial.currency.add'),
					'link' => userpanel\url('settings/financial/currencies/add')
				]
			], 'btns');
		}
		$this->addError($error);
	}
	public function getComparisonsForSelect(){
		return [
			[
				'title' => translator::trans('search.comparison.contains'),
				'value' => 'contains'
			],
			[
				'title' => translator::trans('search.comparison.equals'),
				'value' => 'equals'
			],
			[
				'title' => translator::trans('search.comparison.startswith'),
				'value' => 'startswith'
			]
		];
	}
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if(parent::$navigation){
			$settings = navigation::getByName("settings");
			if(!$financial = navigation::getByName("settings/financial")){
				$financial = new menuItem("financial");
				$financial->setTitle(translator::trans('settings.financial'));
				$financial->setIcon("fa fa-money");
				if($settings)$settings->addItem($financial);
			}
			$currencies = new menuItem("currencies");
			$currencies->setTitle(translator::trans('settings.financial.currencies'));
			$currencies->setURL(userpanel\url('settings/financial/currencies'));
			$currencies->setIcon('fa fa-usd');
			$financial->addItem($currencies);
		}
	}
	public function setButtons(){
		$this->setButton('edit', $this->canEdit, [
			'title' => translator::trans('edit'),
			'icon' => 'fa fa-edit',
			'classes' => ['btn', 'btn-xs', 'btn-teal']
		]);
		$this->setButton('delete', $this->canDel, [
			'title' => translator::trans('delete'),
			'icon' => 'fa fa-times',
			'classes' => ['btn', 'btn-xs', 'btn-bricky']
		]);
	}
}
