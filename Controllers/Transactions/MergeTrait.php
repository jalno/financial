<?php

namespace packages\financial\Controllers\Transactions;

use packages\base\Date;
use packages\base\DB;
use packages\base\Response;
use packages\financial\Authorization;
use packages\financial\Logs;
use packages\financial\Transaction;
use packages\financial\Validators;
use packages\userpanel\Authentication;
use packages\userpanel\Log;

trait MergeTrait
{
    public function merge(): Response
    {
        Authorization::haveOrFail('transactions_merge');

        $inputs = $this->checkInputs([
            'transactions' => [
                'type' => Validators\MergeTranactionsValidator::class,
                'min' => 2,
            ],
            'title' => [
                'type' => 'string',
            ],
            'expire_at' => [
                'type' => 'date',
                'unix' => true,
                'optional' => true,
            ],
        ]);
        if (!isset($inputs['expire_at'])) {
            $inputs['expire_at'] = max(array_map(fn ($t) => $t->expire_at, $inputs['transactions']));
        }

        $mergedTransaction = new Transaction();
        $mergedTransaction->title = $inputs['title'];
        $mergedTransaction->create_at = Date::time();
        $mergedTransaction->expire_at = $inputs['expire_at'];
        foreach ($inputs['transactions'] as $t) {
            if ($t->data['user']) {
                $mergedTransaction->user = $t->data['user'];
                break;
            }
        }
        $mergedTransaction->save();

        foreach ($inputs['transactions'] as $transaction) {
            DB::where('transaction', $transaction->id)
                ->update('financial_transactions_pays', [
                    'transaction' => $mergedTransaction->id,
                ]);
            DB::where('transaction', $transaction->id)
                ->update('financial_transactions_products', [
                    'transaction' => $mergedTransaction->id,
                ]);
            $transaction->delete();
        }

        $payabilePrice = $mergedTransaction->payablePrice();
        $mergedTransaction->status = $payabilePrice > 0 ? Transaction::UNPAID : Transaction::PAID;
        $mergedTransaction->price = $mergedTransaction->totalPrice();
        $mergedTransaction->save();

        $transactionsIDs = array_column($inputs['transactions'], 'id');
        $log = new Log();
        $log->user = Authentication::getID();
        $log->type = Logs\Transactions\Merge::class;
        $log->title = t('financial.logs.transaction.pays.merge');
        $log->parameters = [
            'transactions' => $inputs['transactions'],
            'merged_transaction' => $mergedTransaction,
        ];
        $log->save();

        $this->response->setData($mergedTransaction->toArray(true), 'transaction');
        $this->response->setStatus(true);

        return $this->response;
    }
}
