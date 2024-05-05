<?php

namespace packages\financial\Validators;

use packages\base\DB\Parenthesis;
use packages\base\InputValidationException;
use packages\base\Validator\ArrayValidator;
use packages\financial\Authorization;
use packages\financial\Transaction;
use packages\userpanel\Authentication;
use packages\userpanel\User;

class MergeTranactionsValidator extends ArrayValidator
{
    public function getTypes(): array
    {
        return [];
    }

    /**
     * @return Transaction[]
     */
    public function validate(string $input, array $rule, $data): array
    {
        $me = Authentication::getID();
        $anonymous = Authorization::is_accessed('transactions_anonymous');
        $types = Authorization::childrenTypes();

        $rule['each'] = [
            'type' => Transaction::class,
            'query' => function ($query) use ($me, $anonymous, $types) {
                $query->where('financial_transactions.status', [Transaction::UNPAID, Transaction::PAID], 'IN');
                $query->setQueryOption('MYSQLI_NESTJOIN');
                if ($anonymous) {
                    $query->join(User::class, 'user', 'LEFT');
                    $parenthesis = new Parenthesis();
                    $parenthesis->where('userpanel_users.type', $types, 'IN');
                    $parenthesis->orWhere('financial_transactions.user', null, 'IS');
                    $query->where($parenthesis);
                } else {
                    $query->join(User::class, 'user', 'INNER');
                    if ($types) {
                        $query->where('userpanel_users.type', $types, 'IN');
                    } else {
                        $query->where('userpanel_users.id', $me);
                    }
                }
            },
        ];
        $transactions = parent::validate($input, $rule, $data);
        $users = array_map(fn (Transaction $transaction) => $transaction->data['user'], $transactions);
        if (1 != count(array_unique($users))) {
            throw new InputValidationException($input, 'not-same-users');
        }

        return $transactions;
    }
}
