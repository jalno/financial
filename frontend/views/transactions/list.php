<?php
namespace themes\clipone\views\transactions;
use \packages\base\packages;
use \packages\base\translator;
use \packages\base\view\error;
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
		$this->setTitle([
			translator::trans('transactions'),
			translator::trans('list')
		]);
		$this->setButtons();
		$this->check_multiuser();
		$this->setDates();
		navigation::active("transactions/list");
		if(empty($this->getTransactions())){
			$this->addNotFoundError();
		}
	}
	private function addNotFoundError(){
		$error = new error();
		$error->setType(error::NOTICE);
		$error->setCode('financial.transaction.notfound');
		$btns = [];
		if(packages::package('ticketing')){
			$btns[] = [
				'type' => 'btn-teal',
				'txt' => translator::trans('ticketing.add'),
				'link' => userpanel\url('ticketing/new')
			];
		}
		if($this->canAdd){
			$btns[] = [
				'type' => 'btn-success',
				'txt' => translator::trans('financial.transaction.add'),
				'link' => userpanel\url('transactions/new')
			];
		}
		if($this->canAddingCredit){
			$btns[] = [
				'type' => 'btn-success',
				'txt' => translator::trans('transaction.adding_credit'),
				'link' => userpanel\url('transactions/addingcredit')
			];
		}
		$error->setData($btns, 'btns');
		$this->addError($error);
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
		$this->setButton('transactions_view', $this->canView, [
			'title' => translator::trans('transactions.view'),
			'icon' => 'fa fa-files-o',
			'classes' => ['btn', 'btn-xs', 'btn-green']
		]);
		$this->setButton('transactions_edit', $this->canEdit, [
			'title' => translator::trans('transactions.edit'),
			'icon' => 'fa fa-edit',
			'classes' => ['btn', 'btn-xs', 'btn-warning']
		]);
		$this->setButton('transactions_delete', $this->canDel, [
			'title' => translator::trans('transactions.delete'),
			'icon' => 'fa fa-times',
			'classes' => ['btn', 'btn-xs', 'btn-bricky']
		]);
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
	protected function getStatusForSelect(){
		return [
			[
				'title' => ' ',
				'value' => ' '
			],
			[
				'title' => translator::trans('transaction.unpaid'),
				'value' => transaction::unpaid
			],
			[
				'title' => translator::trans('transaction.paid'),
				'value' => transaction::paid
			],
			[
				'title' => translator::trans('transaction.refund'),
				'value' => transaction::refund
			],
			[
				'title' => translator::trans('transaction.status.expired'),
				'value' => transaction::expired
			]
		];
	}
}
