<?php
namespace packages\financial\logs\transactions;
use \packages\base\view;
use \packages\userpanel\logs;
class add extends logs{
	public function getColor():string{
		return "circle-green";
	}
	public function getIcon():string{
		return "fa fa-money";
	}
	public function buildFrontend(view $view){}
}
