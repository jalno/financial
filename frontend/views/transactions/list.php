<?php
namespace themes\clipone\views\transactions;
use \packages\base\utility;
use \packages\base\translator;
use \packages\base\db\dbObject;
use \packages\base\frontend\theme;

use \packages\userpanel;
use \packages\userpanel\date;

use \themes\clipone\viewTrait;
use \themes\clipone\navigation;
use \themes\clipone\views\listTrait;
use \themes\clipone\views\formTrait;
use \themes\clipone\navigation\menuItem;
use \packages\financial\transaction;
use \packages\financial\authorization;
use \packages\financial\views\transactions\listview as transactionsListView;
class listview extends transactionsListView{
	use viewTrait,listTrait,formTrait;
	protected $multiuser = false;

	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('transactions'),
			translator::trans('list')
		));
		$this->setButtons();
		$this->check_multiuser();
		$this->setDates();
		$this->addAssets();
		navigation::active("transactions/list");
	}
	private function addAssets(){
		$this->addJSFile(theme::url('assets/js/pages/transaction.list.js'));
	}
	public static function onSourceLoad(){
		parent::onSourceLoad();
		if(self::$navigation){
			$item = new menuItem("transactions");
			$item->setTitle("صورتحساب ها");
			$item->setURL(userpanel\url('transactions'));
			$item->setIcon('fa fa-money');
			navigation::addItem($item);
		}
	}
	public function setButtons(){
		$this->setButton('transactions_view', $this->canView, array(
			'title' => translator::trans('transactions.view'),
			'icon' => 'fa fa-files-o',
			'classes' => array('btn', 'btn-xs', 'btn-green')
		));
		$this->setButton('transactions_edit', $this->canEdit, array(
			'title' => translator::trans('transactions.edit'),
			'icon' => 'fa fa-edit',
			'classes' => array('btn', 'btn-xs', 'btn-warning')
		));
		$this->setButton('transactions_delete', $this->canDel, array(
			'title' => translator::trans('transactions.delete'),
			'icon' => 'fa fa-times',
			'classes' => array('btn', 'btn-xs', 'btn-bricky')
		));
	}
	public function check_multiuser(){
		$this->multiuser = (bool)authorization::childrenTypes();
	}
	public function setDates(){
		foreach($this->dataList as $key => $data){
			$this->dataList[$key]->create_at = date::format("Y/m/d H:i:s", $data->create_at);
		}
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
	protected function getStatusForSelect(){
		return array(
			array(
				'title' => ' ',
				'value' => ' '
			),
			array(
				'title' => translator::trans('transaction.unpaid'),
				'value' => transaction::unpaid
			),
			array(
				'title' => translator::trans('transaction.paid'),
				'value' => transaction::paid
			),
			array(
				'title' => translator::trans('transaction.refund'),
				'value' => transaction::refund
			)
		);
	}
}
