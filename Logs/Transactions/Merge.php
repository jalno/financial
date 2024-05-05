<?php
namespace packages\financial\Logs\Transactions;

use packages\base\{View, Translator};
use packages\userpanel\{Date, Logs\Panel, Logs, User};
use packages\financial\{Authorization, Transaction, TransactionPay, TransactionProduct};
use function packages\userpanel\url;

class Merge extends Logs {
	public function getColor(): string {
		return "circle-teal";
	}
	public function getIcon(): string {
		return "fa fa-money";
	}
	
	public function buildFrontend(View $view) {
		$parameters = $this->log->parameters;
		$transactions = $parameters["transactions"];
		$mergedTransaction = $parameters["merged_transaction"];

		$panel = new Panel('packages.financial.logs.transaction.merge');
		$panel->icon = 'fa fa-money';
		$panel->size = 12;
		$panel->title = t('packages.financial.logs.transaction.merged.new_transaction.panel.title');
		$panel->setHTML($this->generateHTMLForTransaction($mergedTransaction));
		$this->addPanel($panel);

		foreach ($transactions as $transaction) {
			$panel = new Panel('packages.financial.logs.transaction.merge');
			$panel->icon = 'fa fa-money';
			$panel->size = 6;
			$panel->title = t('packages.financial.logs.transaction.merged.transaction.panel.title');
			$panel->setHTML($this->generateHTMLForTransaction($transaction));
			$this->addPanel($panel);
		}
	}
	private function generateHTMLForTransaction(Transaction $transaction, bool $isMerged = false) {
		$html = '';
		
		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("transaction.id") . ': </label>';
		$html .= '<div class="col-xs-8">#'. $transaction->id . '</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("transaction.title") . ': </label>';
		$html .= '<div class="col-xs-8">'. $transaction->title . '</div>';
		$html .= "</div>";

		$userHTML = "-";
		if ($transaction->user) {
			$userHTML = '<span class="tooltips" title="#' . $transaction->user->id . '">' . $transaction->user->getFullName() . '</span>';
			if (Authorization::is_accessed("users_view", "userpanel")) {
				$userHTML = '<a href="' . url("users/view/{$transaction->user->id}") . '">' . $userHTML . '</a>';
			}
		}
		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">' . t("transaction.user") . ': </label>';
		$html .= '<div class="col-xs-8">'. $userHTML . '</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'. t("transaction.add.create_at") . ': </label>';
		$html .= '<div class="col-xs-8 ltr">'. Date::format("Q QTS", $transaction->create_at) . '</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'. t("transaction.expire_at") . ': </label>';
		$html .= '<div class="col-xs-8 ltr">'. Date::format("Q QTS", $transaction->expire_at) . '</div>';
		$html .= "</div>";


		$products = array();
		if (isset($transaction->data["products"]) and $transaction->data["products"]) {
			$products = array_map(function($item) {
				return is_array($item) ? new TransactionProduct($item) : $item;
			}, $transaction->data["products"]);
		} else if ($transaction->products) {
			$products = $transaction->products;
		}
		if ($products) {
			$html .= '<label class="control-label">'. t("transaction.products") . ': </label>';
			$html .= '<div class="table-responsive">';
				$html .= '<table class="table table-striped">';
					$html .= "<thead>";
						$html .= "<tr>";
							$html .= "<th>#</th>";
							$html .= "<th>" . t("transaction.title") . "</th>";
							$html .= "<th>" . t("financial.transaction.product.number") . "</th>";
							$html .= "<th>" . t("financial.transaction.product.price_unit") . "</th>";
						$html .= "</tr>";
					$html .= "</thead>";
					$html .= "<tbody>";
			foreach ($products as $product) {
						$html .= "<tr>";
							$html .= "<td>{$product->id}</th>";
							$html .= "<td>{$product->title}</td>";
							$html .= "<td>{$product->number}</th>";
							$html .= "<td><span class=\"ltr\">{$product->price}</span> {$product->currency->title}</td>";
						$html .= "</tr>";
			}
					$html .= "</tbody>";
				$html .= "</table>";
			$html .= "</div>";
		}

		$pays = array();
		if (isset($transaction->data["pays"]) and $transaction->data["pays"]) {
			$pays = array_map(function($item) {
				return is_array($item) ? new TransactionPay($item) : $item;
			}, $transaction->data["pays"]);
		} else if ($transaction->pays) {
			$pays = $transaction->pays;
		}
		if ($pays) {
			$html .= '<label class="control-label">'. t("financial.pays.information") . ': </label>';
			$html .= '<div class="table-responsive">';
				$html .= '<table class="table table-striped">';
					$html .= "<thead>";
						$html .= "<tr>";
							$html .= "<th>#</th>";
							$html .= "<th>" . t("date&time") . "</th>";
							$html .= "<th>" . t("transaction.price") . "</th>";
							$html .= "<th>" . t("pay.status") . "</th>";
						$html .= "</tr>";
					$html .= "</thead>";
					$html .= "<tbody>";
			foreach ($pays as $pay) {
						$html .= "<tr>";
							$html .= "<td>{$pay->id}</th>";
							$html .= "<td class=\"ltr\">" . Date::format("Q QTS", $pay->date) . "</th>";
							$html .= "<td><span class=\"ltr\">{$pay->price}</span> {$pay->currency->title}</td>";
							$html .= "<td><span class=\"{$this->getPayStatusClass($pay)}\">{$this->getPayStatusText($pay)}</span></th>";
						$html .= "</tr>";
			}
					$html .= "</tbody>";
				$html .= "</table>";
			$html .= "</div>";
		}

		return $html;
	}
	private function getPayStatusText(TransactionPay $pay): string {
		switch ($pay->status) {
			case TransactionPay::rejected: return t('pay.rejected');
			case TransactionPay::accepted: return t('pay.accepted');
			case TransactionPay::pending: return t('pay.pending');
			case TransactionPay::REIMBURSE: return t('pay.reimburse');
			default: return "";
			
		}
	}
	private function getPayStatusClass(TransactionPay $pay): string {
		switch ($pay->status) {
			case TransactionPay::rejected: return 'label label-danger';
			case TransactionPay::accepted: return 'label label-success';
			case TransactionPay::pending: return 'label label-warning';
			case TransactionPay::REIMBURSE: return 'label label-info';
			default: return "";
		}
	}
}
