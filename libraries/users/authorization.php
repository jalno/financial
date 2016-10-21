<?php
namespace packages\financial;
use \packages\userpanel\authorization as UserPanelAuthorization;
class authorization extends UserPanelAuthorization{
	static function is_accessed($permission, $prefix = 'financial'){
		return parent::is_accessed($permission, $prefix);
	}
	static function haveOrFail($permission, $prefix = 'financial'){
		parent::haveOrFail($permission, $prefix);
	}
}
