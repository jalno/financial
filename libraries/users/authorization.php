<?php
namespace packages\financial;
use \packages\userpanel\authorization as UserPanelAuthorization;
use \packages\userpanel\authentication;
class authorization extends UserPanelAuthorization{
	static function is_accessed($permission, $prefix = 'financial'){
		return parent::is_accessed($permission, $prefix);
	}
}
