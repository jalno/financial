<?php
namespace packages\financial\Events\Transactions;
use \packages\base\Event;
use \packages\userpanel\User;
use \packages\notifications\Notifiable;
use \packages\financial\Transaction;
use \packages\financial\Authentication;
class Add extends Event implements Notifiable{
	public $transaction;
	public function __construct(transaction $transaction){
		$this->transaction = $transaction;
	}
	public function getTransaction():Transaction{
		return $this->transaction;
	}
	public static function getName():string{
		return 'financial_transaction_add';
	}
	public static function getParameters():array{
		return [Transaction::class];
	}
	public function getArguments():array{
		return [
			'transaction' => $this->getTransaction()
		];
	}
	public function getTargetUsers():array{
		$users = [$this->transaction->user];
		return $users;
	}
}