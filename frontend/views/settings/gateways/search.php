<?php
namespace themes\clipone\views\financial\settings\gateways;

use \packages\base\translator;
use \packages\base\frontend\theme;

use \packages\userpanel;
use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation\menuItem;

use \packages\financial\views\settings\gateways\search as gatewaysListview;
use \packages\financial\payport as gateway;

class search extends gatewaysListview{
	use viewTrait, listTrait, formTrait;
	private $categories;
	function __beforeLoad(){
		$this->setTitle(translator::trans("settings.financial.gateways"));
		navigation::active("settings/financial/gateways");
		$this->setButtons();
	}
	public function getComparisonsForSelect(){
		return array(
			array(
				'title' => translator::trans('search.comparison.contains'),
				'value' => 'contains'
			),
			array(
				'title' => translator::trans('search.comparison.equals'),
				'value' => 'equals'
			),
			array(
				'title' => translator::trans('search.comparison.startswith'),
				'value' => 'startswith'
			)
		);
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
			$gateways = new menuItem("gateways");
			$gateways->setTitle(translator::trans('settings.financial.gateways'));
			$gateways->setURL(userpanel\url('settings/financial/gateways'));
			$gateways->setIcon('fa fa-rss');
			$financial->addItem($gateways);
		}
	}
	public function setButtons(){
		$this->setButton('edit', $this->canEdit, array(
			'title' => translator::trans('edit'),
			'icon' => 'fa fa-edit',
			'classes' => array('btn', 'btn-xs', 'btn-warning')
		));
		$this->setButton('delete', $this->canDel, array(
			'title' => translator::trans('delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky')
		));
	}
}
