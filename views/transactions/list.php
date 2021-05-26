<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\listview as list_view;
use \packages\financial\authorization;
use \packages\base\views\traits\form as formTrait;
class listview extends  list_view{
	use formTrait;
	protected $canView;
	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	protected $canAddingCredit;
	static protected $navigation;
	function __construct(){
		$this->canAddingCredit = authorization::is_accessed('transactions_addingcredit');
		$this->canAdd = authorization::is_accessed('transactions_add');
		$this->canView = authorization::is_accessed('transactions_view');
		$this->canEdit = authorization::is_accessed('transactions_edit');
		$this->canDel = authorization::is_accessed('transactions_delete');
	}
	static function onSourceLoad(){
		self::$navigation = authorization::is_accessed('transactions_list');
	}
	protected function getTransactions():array{
		return $this->dataList;
	}
}
