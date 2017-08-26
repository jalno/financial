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
			'transactions_addingcredit',
			'transactions_accept',
			'transactions_product_edit',
			'transactions_product_delete',

			'settings_bankaccounts_list',
			'settings_bankaccounts_add',
			'settings_bankaccounts_delete',
			'settings_bankaccounts_edit',
			
			"settings_gateways_search",
			"settings_gateways_add",
			"settings_gateways_edit",
			"settings_gateways_delete",

			"transactions_product_config",

			"settings_currencies_search",
			"settings_currencies_add",
			"settings_currencies_edit",
			"settings_currencies_delete"

		);
		foreach($permissions as $permission){
			permissions::add('financial_'.$permission);
		}
	}
}
