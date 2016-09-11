<?php
namespace packages\financial\views\transactions;
use \packages\financial\views\listview as list_view;
use \packages\financial\authorization;

class listview extends  list_view{
	protected $canAdd;
	protected $canEdit;
	protected $canDel;
	static protected $navigation;
	function __construct(){
		$this->canAdd = authorization::is_accessed('transactions_add');
		$this->canView = authorization::is_accessed('transactions_view');
		$this->canEdit = authorization::is_accessed('transactions_edit');
		$this->canDel = authorization::is_accessed('transactions_del');
	}

	static function onSourceLoad(){
		self::$navigation = authorization::is_accessed('transactions_list');
	}
}
