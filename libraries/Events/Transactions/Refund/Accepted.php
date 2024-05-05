<?php
namespace packages\financial\Events\Transactions\Refund;

use packages\base\Event;
use packages\notifications\Notifiable;
use packages\financial\Transaction;

class Accepted extends Event implements Notifiable {
	/**
	 * @var Transaction
	 */
	private $transaction;

	public function __construct(Transaction $transaction){
		$this->transaction = $transaction;
	}

	public function getTransaction(): Transaction {
		return $this->transaction;
	}
	public static function getName(): string {
		return 'financial_transaction_refund_accepted';
	}
	public static function getParameters(): array {
		return [Transaction::class];
	}
	public function getArguments(): array {
		return [
			'Transaction' => $this->getTransaction()
		];
	}
	public function getTargetUsers(): array {
		return [$this->transaction->user];
	}
}