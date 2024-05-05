<?php

namespace packages\financial\Processes;

use packages\base\Cache;
use packages\base\Date;
use packages\base\Log;
use packages\base\Options;
use packages\base\Process;
use packages\base\Response;
use packages\financial\Currency;
use packages\financial\Events;
use packages\financial\Transaction;

class Transactions extends Process
{
    public function reminder(): Response
    {
        $response = new Response();
        $log = Log::getInstance();
        $log->info('get remind day from options');
        $days = Options::get('packages.financial.transactions.reminder');
        $log->reply('done', $days);
        $log->info('sorting days in ASC (low to high)');
        sort($days);
        $log->reply('done', $days);
        $log->info('get unpaid transactions');
        $transaction = new Transaction();
        $transaction->where('status', Transaction::unpaid);
        $transaction->where('expire_at', null, 'is not');
        $transaction->where('expire_at', Date::time() + max($days), '<');
        $transactions = $transaction->get(null, 'financial_transactions.*');
        $log->reply(count($transactions), ' trasnaction found');
        foreach ($transactions as $transaction) {
            $log->info('transaction #', $transaction->id);
            foreach ($days as $day) {
                $trigger = $transaction->param('trigger_remind');
                if ((!$trigger or $trigger > $day) and Date::time() + $day >= $transaction->expire_at) {
                    $transaction->setParam('trigger_remind', $day);
                    $log->info('try to send notification for ', $day, ' remind day');
                    $event = new Events\Transactions\Reminder($transaction);
                    $event->trigger();
                    $log->reply('done');
                    break;
                }
            }
        }
        $response->setStatus(true);

        return $response;
    }

    public function autoExpire(array $data): Response
    {
        $response = new Response(false);
        $log = Log::getInstance();

        $dryRun = $data['dry-run'] ?? false;
        if ($dryRun) {
            $log->warn('run in dry-run mode, nothing will effective');
        }
        $forceRun = $data['force'] ?? false;
        $log->debug('check another process is running?');
        if (Cache::has('packages.financial.processes.autoExpire.lock')) {
            $log->reply()->warn('another process still runing.');
            if (!$forceRun) {
                $log->warn('you can run with --force option at your own risk');
                $response->setStatus(true);

                return $response;
            }
            $log->warn('you ran that with --force option at your risk!');
        } else {
            $log->reply('not running');
        }
        $log->info('set lock to avoid another process run simultaneously');
        Cache::set('packages.financial.processes.autoExpire.lock', true, 0);

        $log->info('get expired unpaid transactions for expire');
        $transactions = (new Transaction())
        ->where('status', Transaction::unpaid)
        ->where('expire_at', null, 'IS NOT')
        ->where('expire_at', Date::time(), '<')
        ->get();
        $log->reply(count($transactions), 'found');

        $log->info('try expire each transaction');
        foreach ($transactions as $transaction) {
            $log->info('try expire transaction: #'.$transaction->id);
            try {
                if ($dryRun) {
                    $log->reply()->warn('run in dry run mode');
                } else {
                    $transaction->expire();
                }
                $log->reply('done');
            } catch (Currency\UnChangableException $e) {
                $log->reply()->error("can not expire this transaction due Currency\UnChangableException exception");
            }
        }
        $log->info('reach to end of process, remove lock');
        Cache::delete('packages.financial.processes.autoExpire.lock');
        $response->setStatus(true);

        return $response;
    }
}
