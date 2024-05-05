<?php
namespace packages\financial\Events\Transactions;
use \packages\base\Event;
use \packages\userpanel\User;
use \packages\notifications\Notifiable;
use \packages\financial\Transaction;
use \packages\financial\Authentication;
class Expire extends Event implements Notifiable{
	private $transaction;
	public function __construct(Transaction $transaction){
		$this->transaction = $transaction;
	}
	public function getTransaction():Transaction{
		return $this->transaction;
	}
	public static function getName():string{
		return 'financial_transaction_expire';
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
		$parents = $this->transaction->user->parentTypes();
		$users = [];
		if($parents){
			$user = new User();
			$user->where("type", $parents, 'in');
			foreach($user->get() as $user){
				$users[$user->id] = $user;
			}
		}
		$users[$this->transaction->user->id] = $this->transaction->user;
		return $users;
	}
}