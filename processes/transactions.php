<?php
namespace packages\financial\processes;
use \packages\base\log;
use \packages\base\date;
use \packages\base\process;
use \packages\base\options;
use \packages\base\response;
use \packages\financial\events;
use \packages\financial\transaction;
class transactions extends process{
	public function reminder():response{
		log::setLevel('debug');
		$response = new response();
		$log = log::getInstance();
		$log->info('get remind day from options');
		$days = options::get('packages.financial.transactions.reminder');
		$log->reply("done");

		$log->info('get unpaid transactions');
		$transaction = new transaction();
		$transaction->where('status', transaction::unpaid);
		$transaction->where('expire_at', date::time() + max($days), "<");
		$transactions = $transaction->get(null, "financial_transactions.*");
		$log->reply(count($transactions), " trasnaction found");
		foreach($transactions as $transaction){
			$log->info('transaction #', $transaction->id);
			foreach($days as $day){
				if($transaction->expire_at - date::time() < $day){
					$log->info('try to send notification for ', $day, ' remind day');
					$event = new events\transactions\reminder($transaction);
					$event->trigger();
					$log->reply("done");
				}
			}
		}
		$response->setStatus(true);
		return $response;
	}
}