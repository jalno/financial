<?php
namespace packages\financial\logs\transactions;
use \packages\base\view;
use \packages\userpanel\logs;
class delete extends logs{
	public function getColor():string{
		return "circle-bricky";
	}
	public function getIcon():string{
		return "fa fa-money";
	}
	public function buildFrontend(view $view){}
}
