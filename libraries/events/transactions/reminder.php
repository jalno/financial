<?php
namespace packages\financial\events\transactions;
use \packages\base\event;
use \packages\userpanel\user;
use \packages\notifications\notifiable;
use \packages\financial\transaction;
use \packages\financial\authentication;
class reminder extends event implements notifiable{
	private $transaction;
	public function __construct(transaction $transaction){
		$this->transaction = $transaction;
	}
	public function getTransaction():transaction{
		return $this->transaction;
	}
	public static function getName():string{
		return 'financial_transaction_reminder';
	}
	public static function getParameters():array{
		return [transaction::class];
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