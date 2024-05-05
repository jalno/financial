<?php

namespace packages\financial\Listeners;

use packages\base\DB;
use packages\base\DB\Parenthesis;
use packages\base\Translator;
use packages\financial\Authorization;
use packages\financial\Transaction;
use packages\userpanel;
use packages\userpanel\Date;
use packages\userpanel\Events\Search as Event;
use packages\userpanel\Search as SearchHandler;
use packages\userpanel\Search\Link;

class Search
{
    public function find(Event $e)
    {
        if (Authorization::is_accessed('transactions_list')) {
            $this->transActions($e->word);
        }
    }

    public function transActions($word)
    {
        $anonymous = Authorization::is_accessed('transactions_anonymous');

        DB::join('userpanel_users', 'userpanel_users.id=financial_transactions.user', $anonymous ? 'LEFT' : 'INNER');
        DB::join('financial_transactions_products', 'financial_transactions_products.transaction=financial_transactions.id', 'LEFT');
        $transaction = new Transaction();
        $parenthesis = new Parenthesis();
        foreach (['title', 'description'] as $item) {
            $parenthesis->where("financial_transactions_products.{$item}", $word, 'contains', 'OR');
        }
        foreach (['title'] as $item) {
            $parenthesis->where("financial_transactions.{$item}", $word, 'contains', 'OR');
        }
        $transaction->where($parenthesis);
        foreach ($transaction->get(null, 'financial_transactions.*') as $transaction) {
            $result = new Link();
            $result->setLink(userpanel\url("transactions/view/{$transaction->id}"));
            $result->setTitle(Translator::trans('financial.transactions', [
                'title' => $transaction->title,
            ]));
            $result->setDescription(Translator::trans('financial.transactions.description', [
                'id' => $transaction->id,
                'create_at' => Date::format('Y/m/d H:i:s', $transaction->create_at),
                'user' => $transaction->user ? $transaction->user->getFullName() : null,
            ]));
            SearchHandler::addResult($result);
        }
    }
}
