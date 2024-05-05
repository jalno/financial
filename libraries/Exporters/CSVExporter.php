<?php
namespace packages\financial\Exporters;

use packages\base\{Response\File, IO, DB};
use packages\financial\TransactionPay;
use packages\userpanel\Date;
use packages\financial\{Transaction, TransactionProduct, TransactionsProductsParam, Transaction\IExporterHandler};

class CSVExporter implements IExporterHandler {

	/**
	 * @var bool
	 */
	protected $isAllRefund = false;

	/**
	 * @var Transaction[]|null
	 */
	protected $transactions;

	/**
	 * @var TransactionsProductsParam[]|null
	 */
	protected $bankAccountsInfo;

	public function export(array $transactions): File {
		$this->transactions = $transactions;

		$isAllRefund = !empty($this->transactions);
		foreach ($this->transactions as $transaction) {
			if ($transaction->price > 0) {
				$isAllRefund = false;
				break;
			}
		}
		$this->isAllRefund = $isAllRefund;

		$this->prepareTransactions();
		$csv = $this->getHeader() . "\n";
		foreach ($this->transactions as $transaction) {
			$csv .= $this->exportTransaction($transaction) . "\n";
		}
		$tmp = new IO\File\TMP();
		$tmp->write($csv);
		$file = new File();
		$file->setLocation($tmp);
		$file->setSize($tmp->size());
		$file->setName("financial-transactions.csv");
		$file->setMimeType("text/csv", "utf-8");
		return $file;
	}

	protected function getHeader(): string {
		$header = t("packages.financial.transaction.id") . ";" .
			t("packages.financial.transaction.title") . ";" .
			t("packages.financial.transaction.create_at") . ";" .
			t("packages.financial.transaction.user") . ";" .
			t("packages.financial.transaction.user.cellphone") . ";" .
			t("packages.financial.transaction.user.email") . ";" .
			t("packages.financial.transaction.price") . ";" .
			t("packages.financial.transaction.price.paid") . ";" .
			t("packages.financial.transaction.price.payable") . ";";
		if ($this->isAllRefund) {
			$header .= t("packages.financial.bankaccount.credit_cart") . ";" .
				t("packages.financial.bankaccount.shaba") . ";";
		} else {
			$header .= t('titles.financial.paid_at').';';
			$header .= t('titles.financial.pays_methods').';';
		}
		$header .= t("packages.financial.transaction.status");
		return $header;
	}

	protected function prepareTransactions(): void {
		$this->prepareRefundBankInfo();
	}

	protected function prepareRefundBankInfo(): void {
		if (!$this->isAllRefund or !$this->transactions) {
			return;
		}
		DB::join("financial_transactions_products", "financial_transactions_products_params.product=financial_transactions_products.id", "INNER");
		DB::joinWhere("financial_transactions_products", "financial_transactions_products.method", TransactionProduct::refund);

		$param = new TransactionsProductsParam();
		$param->where("financial_transactions_products.transaction", array_column($this->transactions, 'id'), "IN");
		$param->where("financial_transactions_products_params.name", "bank-account");
		$this->bankAccountsInfo = $param->get(null, array(
			"financial_transactions_products.transaction",
			"financial_transactions_products_params.*",
		));
	}
	protected function getRefundBankInfo(int $transaction): ?TransactionsProductsParam {
		foreach ($this->bankAccountsInfo as $param) {
			if ($param->transaction === $transaction) {
				return $param->value;
			}
		}
		return null;
	}
	protected function exportTransaction(Transaction $transaction) {
		$createAt = Date::format("Y/m/d H:i", $transaction->create_at);
		$price = abs($transaction->price);
		$payablePrice = abs($transaction->payablePrice());
		$paid = $price - $payablePrice;
		$status = '';
		switch ($transaction->status) {
			case (Transaction::unpaid):
				$status = t("packages.financial.transaction.status.unpaid");
				break;
			case (Transaction::paid):
				$status = t("packages.financial.transaction.status.paid");
				break;
			case (Transaction::refund):
				$status = t("packages.financial.transaction.status.refund");
				break;
			case (Transaction::expired):
				$status = t("packages.financial.transaction.status.expired");
				break;
			case (Transaction::rejected):
				$status = t("packages.financial.transaction.status.rejected");
				break;
		}
		$csv  = $transaction->id . ";";
		$csv .= $transaction->title . ";";
		$csv .= $createAt . ";";
		$csv .= ($transaction->user ? $transaction->user->getFullName() : "-") . ";";
		$csv .= ($transaction->user ? $transaction->user->cellphone : "-") . ";";
		$csv .= ($transaction->user ? $transaction->user->email : "-") . ";";
		$csv .= $price . " " . $transaction->currency->title . ";";
		$csv .= $paid . " " . $transaction->currency->title . ";";
		$csv .= $payablePrice . " " . $transaction->currency->title . ";";
		if ($this->isAllRefund) {
			$account = $this->getRefundBankInfo($transaction->id);
			if ($account) {
				if (isset($account["cart"]) and $account["cart"]) {
					$csv .= "{$account['cart']};";
				} else {
					$csv .= "-;";
				}
				if (isset($account["shaba"]) and $account["shaba"]) {
					$csv .= "{$account['shaba']};";
				} else {
					$csv .= "-;";
				}
			}
		} else {
			$getTransactionPayGates = function(Transaction $transaction): string {
				$pays = [];

				foreach ($transaction->pays as $pay) {
					switch ($pay->method) {
						case TransactionPay::CREDIT:
							$pays[] = t('pay.method.credit');
							break;
						case TransactionPay::BANKTRANSFER:
							$pays[] = t('pay.byBankTransfer');
							break;
						case TransactionPay::ONLINEPAY:
							$pays[] = t('pay.byPayOnline');
							break;
						case TransactionPay::PAYACCEPTED:
							$pays[] = t('titles.financial.accepted');
							break;
					}
				}

				if (empty($pays)) {
					$pays[] = '-';
				}

				return implode('، ', array_unique($pays)).';';
			};

			$csv .= Date::format('Y/m/d H:i', $transaction->paid_at).';';
			$csv .= $getTransactionPayGates($transaction);
		}
		$csv .= $status.';';
		return $csv;
	}
}
