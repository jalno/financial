<?php
namespace packages\financial;
use \packages\userpanel\Authorization as UserPanelAuthorization;
class Authorization extends UserPanelAuthorization{
	static function is_accessed($permission, $prefix = 'financial'){
		return parent::is_accessed($permission, $prefix);
	}
	static function haveOrFail($permission, $prefix = 'financial'){
		parent::haveOrFail($permission, $prefix);
	}
}
