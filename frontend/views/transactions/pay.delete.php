<?php
namespace themes\clipone\views\transactions\pay;
use \packages\financial\views\transactions\pay\delete as payDelete;
use \themes\clipone\viewTrait;
use \themes\clipone\views\listTrait;
use \packages\base\translator;

class delete extends payDelete{
	use viewTrait,listTrait;
	protected $transaction;
	protected $pays;
	protected $hasdesc;
	function __beforeLoad(){
		$this->setTitle(array(
			translator::trans('delete'),
			$this->getPayData()->id
		));
		$this->setShortDescription(translator::trans('pay.delete'));
	}
}
