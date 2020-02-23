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
			"transactions_anonymous",
			"transactions_refund",
			"transactions_refund_accept",
			
			'transactions_pays_accept',
			'transactions_pay_delete',
			'transactions_pay_edit',
			'transactions_addingcredit',
			'transactions_accept',
			'transactions_product_edit',
			'transactions_product_delete',
			
			
			"settings_banks_search",
			"settings_banks_add",
			"settings_banks_edit",
			"settings_banks_delete",
			
			"settings_banks_accounts_search",
			"settings_banks_accounts_add",
			"settings_banks_accounts_accept",
			"settings_banks_accounts_edit",
			"settings_banks_accounts_delete",
			
			"settings_gateways_search",
			"settings_gateways_add",
			"settings_gateways_edit",
			"settings_gateways_delete",
			
			"transactions_product_config",
			
			"settings_currencies_search",
			"settings_currencies_add",
			"settings_currencies_edit",
			"settings_currencies_delete",
			'transactions_guest_pay_link',
		);
		foreach($permissions as $permission){
			permissions::add('financial_'.$permission);
		}
	}
}
