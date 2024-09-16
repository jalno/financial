<?php

namespace packages\financial\Views\Transactions;

use packages\base\DB\DBObject;
use packages\base\View\Error;
use packages\base\Views\Traits\Form as FormTrait;
use packages\financial\Authorization;
use packages\financial\Currency;
use packages\financial\Transaction;
use packages\financial\Views\ListView as ParentListView;
use packages\userpanel\{Authentication};

class ListView extends ParentListView
{
    use FormTrait;
    
    protected $canView;
    protected $canAdd;
    protected $canEdit;
    protected $canDel;
    protected $canAddingCredit;

    public function __construct()
    {
        $this->canAddingCredit = Authorization::is_accessed('transactions_addingcredit');
        $this->canAdd = Authorization::is_accessed('transactions_add');
        $this->canView = Authorization::is_accessed('transactions_view');
        $this->canEdit = Authorization::is_accessed('transactions_edit');
        $this->canDel = Authorization::is_accessed('transactions_delete');
    }

    public function export(): array
    {
        $export = [
            'data' => [
                'items' => DBObject::objectToArray($this->dataList, true),
                'items_per_page' => (int) $this->itemsPage,
                'current_page' => (int) $this->currentPage,
                'total_items' => (int) $this->totalItems,
            ],
        ];

        $me = Authentication::getUser();
        $userCurrency = Currency::getDefault($me);
        $userCurrencyArray = $userCurrency->toArray();

        $export['data']['balance'] = [
            'amount' => $me->credit,
            'currency' => $userCurrencyArray,
        ];

        $unpaidTransactions = (new Transaction())
            ->where('user', $me->id)
            ->where('status', Transaction::UNPAID)
        ->get();

        $debt = 0;
        $error = null;
        foreach ($unpaidTransactions as $t) {
            try {
                $debt += $t->currency->changeTo($t->price, $userCurrency);
            } catch (Currency\UnChangableException $e) {
                if (!$error) {
                    $error = new Error('packages.financial.views.transactions.ListView.export.debt.unchangeable_price_exception');
                    $error->setTraceMode(Error::NO_TRACE);
                }
            }
        }
        $export['data']['debt'] = [
            'amount' => $debt,
            'currency' => $userCurrencyArray,
        ];
        if ($error) {
            $export['data']['debt']['error'] = [$error];
        }

        return $export;
    }

    protected function getTransactions(): array
    {
        return $this->dataList;
    }
}
