<?php
namespace packages\financial\Logs\Transactions;

use packages\base\{View, Translator};
use packages\userpanel\{Date, Logs\Panel, Logs, User};
use packages\financial\TransactionPay;

class Reimburse extends Logs {
	public function getColor(): string {
		return "circle-teal";
	}
	public function getIcon(): string {
		return "fa fa-money";
	}
	
	public function buildFrontend(View $view) {
		$parameters = $this->log->parameters;
		$pays = $parameters['pays'];
		$user = $parameters['user'];
		$userCurrency = $parameters['user_currency'];

		$panel = new Panel('financial.logs.transaction.reimburse');
		$panel->icon = 'fa fa-money';
		$panel->size = 6;
		$panel->title = t('financial.pays.information');
		$html = '';

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("packages.financial.logs.transaction.reimburse.transaction.user.id") . ': </label>';
		$html .= '<div class="col-xs-8">#'. $user->id . '</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("packages.financial.logs.transaction.reimburse.transaction.user_full_name") . ': </label>';
		$html .= '<div class="col-xs-8">'. $user->getFullName() . '</div>';
		$html .= "</div>";
		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("packages.financial.logs.transaction.reimburse.user_credit_after_reimburse") . ': </label>';
		$html .= '<div class="col-xs-8">'. $user->credit . ' ' . $userCurrency->title . '</div>';
		$html .= "</div>";

		$panel->setHTML($html);
		$this->addPanel($panel);

		$payPanel = $this->getPaysPanel($pays);
		$this->addPanel($payPanel);
	}

	private function getPaysPanel(array $pays): Panel {
		$panel = new Panel('financial.logs.transaction.pay');
		$panel->icon = 'fa fa-money';
		$panel->size = 6;
		$panel->title = t('financial.pays.information');

		$table = '<table class="table table-striped table-hover">';
			$table.= '<thead>';
					$table .= '<tr>';
						$table .= '<th> # </th>';
						$table .= '<th>' . t('date&time') . '</th>';
						$table .= '<th>' . t('pay.method') . '</th>';
						$table .= '<th>' . t('pay.price') . '</th>';
					$table .= '</tr>';
			$table .= '</thead>';
			$table .= '<tbody>';

			foreach ($pays as $pay) {
				$table .= '<tr>';
					$table .= '<td>#' . $pay->id . '</td>';
					$table .= '<td class="text-center" dir="ltr">' . Date::format("Y/m/d H:i:s", $pay->date) . '</td>';
					$table .= '<td>' . $this->getPayTitle($pay) . '</td>';
					$table .= '<td>' . $pay->price . ' ' . $pay->currency->title . '</td>';
				$table .= '</tr>';
			}

			$table .= '</tbody>';
		$table .= '</table>';

		$panel->setHTML($table);

		return $panel;
	}
	private function getPayTitle(TransactionPay $pay): string {
		switch ($pay->method) {
			case TransactionPay::credit:
				return t('pay.method.credit');
			case TransactionPay::banktransfer:
				return t('pay.byBankTransfer');
			case TransactionPay::onlinepay:
				return t('pay.byPayOnline');
			case TransactionPay::payaccepted:
				$acceptorID = $pay->param('acceptor');
				$acceptor = $acceptorID ? (new User)->byID($acceptorID) : null;
				return t('pay.method.payaccepted', array('acceptor' => $acceptor ? $acceptor->getFullName() : "-"));
			default:
				return "";
		}
	}
}
