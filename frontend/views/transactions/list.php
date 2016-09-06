<?php
namespace themes\clipone\views\transactions;
use \packages\base\db\dbObject;
use \packages\financial\views\transactions\listview as transactionsListView;
use \packages\userpanel;
use \packages\userpanel\date;
use \themes\clipone\navigation;
use \themes\clipone\navigation\menuItem;
use \themes\clipone\views\listTrait;
use \packages\base\translator;
use \packages\base\utility;

class listview extends transactionsListView{
	use listTrait;
	protected $multiuser = false;

	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('transactions'),
			translator::trans('list'),
		));
		$this->setButtons();
		$this->check_multiuser();
		$this->setDates();
		navigation::active("transactions/list");
	}
	public static function onSourceLoad(){
		$item = new menuItem("transactions");
		$item->setTitle("صورت حساب ها");
		$item->setURL(userpanel\url('transactions'));
		$item->setIcon('clip-data');
		navigation::addItem($item);
	}
	public function setButtons(){
		$this->setButton('transactions_view', $this->canView, array(
			'title' => translator::trans('transactions.view'),
			'icon' => 'fa fa-files-o',
			'classes' => array('btn', 'btn-xs', 'btn-green')
		));
		$this->setButton('transactions_edit', $this->canEdit, array(
			'title' => translator::trans('transactions.edit'),
			'icon' => 'fa fa-files-o',
			'classes' => array('btn', 'btn-xs', 'btn-green')
		));
		$this->setButton('transactions_del', $this->canDel, array(
			'title' => translator::trans('transactions.del'),
			'icon' => 'fa fa-files-o',
			'classes' => array('btn', 'btn-xs', 'btn-green')
		));
	}
	public function check_multiuser(){
		if($this->dataList){
			$users = array_unique(array_column(dbObject::objectToArray($this->dataList), 'user'));
			$this->multiuser = count($users) > 1;
		}
	}
	public function setDates(){
		foreach($this->dataList as $key => $data){
			$this->dataList[$key]->create_at = date::format("Y/m/d H:i:s", $data->create_at);
		}
	}
}
