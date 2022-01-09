<?php
namespace themes\clipone\views;

use packages\financial\Transaction;

class TransactionUtilities {
	public static function getStatusLabelSpan(Transaction $transaction): ?string {
		switch ($transaction->status) {
			case Transaction::UNPAID:
				return '<span class="label label-danger">' .
					t('transaction.unpaid') .
				'</span>';
			case Transaction::PENDING:
				return '<span class="label label-warning label-pending">' .
					t('transaction.pending') .
				'</span>';
			case Transaction::PAID:
				return '<span class="label label-success">' .
					t('transaction.paid') .
				'</span>';
			case Transaction::REFUND:
				return '<span class="label label-warning">' .
					t('transaction.refund') .
				'</span>';
			case Transaction::EXPIRED:
				return '<span class="label label-inverse">' .
					t('transaction.status.expired') .
				'</span>';
			case Transaction::REJECTED:
				return '<span class="label label-danger label-rejected">' .
					t('packages.financial.transaction.status.rejected') .
				'</span>';
		}
		return null;
	}
}
