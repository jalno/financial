<?php
namespace packages\financial\listeners\settings;
use \packages\userpanel\usertype\permissions;
class usertype{
	public function permissions_list(){
		$permissions = array(
			'transactions_list',
			'transactions_add',
			'transactions_edit',
			'transactions_delete',
			'transactions_view',

			'transactions_pays_accept',
			'transactions_pay_delete',
			'transactions_product_delete',
			'transactions_addingcredit',
			'transactions_accept',

			'settings_bankaccounts_list',
			'settings_bankaccounts_add',
			'settings_bankaccounts_delete',
			'settings_bankaccounts_edit'

		);
		foreach($permissions as $permission){
			permissions::add('financial_'.$permission);
		}
	}
}
