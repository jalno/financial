<?php
namespace themes\clipone\Listeners\Financial;

use packages\financial\Authorization;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\Dashboard;

use function packages\userpanel\url;

class NavigationListener {
	public function initial(): void
	{
		if (Authorization::is_accessed('transactions_list')) {
			$item = new MenuItem('transactions');
			$item->setTitle(t('packages.financial.transactions'));
			$item->setURL(url('transactions'));
			$item->setIcon('fa fa-money');
			Navigation::addItem($item);
		}

		if (Authorization::is_accessed('settings_banks_search')) {
			$bank = new MenuItem('banks');
			$bank->setTitle(t('packages.financial.banks'));
			$bank->setURL(url('settings/financial/banks'));
			$bank->setIcon('fa fa-university');
			$this->getFinancialSettings()->addItem($bank);
		}

		if (Authorization::is_accessed('settings_banks_accounts_search')) {
			$bankaccount = new MenuItem('bankaccounts');
			$bankaccount->setTitle(t('packages.financial.banks.accounts'));
			$bankaccount->setURL(url('settings/financial/banks/accounts'));
			$bankaccount->setIcon('fa fa-credit-card');
			$this->getFinancialSettings()->addItem($bankaccount);
		}

		if (Authorization::is_accessed('settings_banks_accounts_search')) {
			$bankaccount = new MenuItem('bankaccounts');
			$bankaccount->setTitle(t('packages.financial.banks.accounts'));
			$bankaccount->setURL(url('settings/financial/banks/accounts'));
			$bankaccount->setIcon('fa fa-credit-card');
			$this->getFinancialSettings()->addItem($bankaccount);
		}

		if (Authorization::is_accessed('settings_currencies_search')) {
			$currencies = new MenuItem('currencies');
			$currencies->setTitle(t('settings.financial.currencies'));
			$currencies->setURL(url('settings/financial/currencies'));
			$currencies->setIcon('fa fa-usd');
			$this->getFinancialSettings()->addItem($currencies);
		}

		if (Authorization::is_accessed('settings_gateways_search')) {
			$gateways = new MenuItem('gateways');
			$gateways->setTitle(t('settings.financial.gateways'));
			$gateways->setURL(url('settings/financial/gateways'));
			$gateways->setIcon('fa fa-rss');
			$this->getFinancialSettings()->addItem($gateways);
		}
	}

	protected function getFinancialSettings(): MenuItem
	{
		$settings = Navigation::getByName('settings/financial');
		if (!$settings) {
			$settings = new MenuItem('financial');
			$settings->setTitle(t('settings.financial'));
			$settings->setIcon('fa fa-money');
			Dashboard::getSettingsMenu()->addItem($settings);
		}

		return $settings;
	}
}