<?php

namespace packages\financial\Views\Transactions;

use packages\financial\Authorization;
use packages\financial\Transaction;

class Pay extends \packages\financial\View
{
    use PayTrait;
    protected $canAccept;
    protected $canViewGuestLink;

    public function __construct()
    {
        $this->canAccept = Authorization::is_accessed('transactions_accept');
        $this->canViewGuestLink = Authorization::is_accessed('transactions_guest-pay-link');
        $this->setData([], 'methods');
    }

    public function setCredit($credit)
    {
        $this->setData($credit, 'credit');
    }

    public function setBankAccounts($accounts)
    {
        $this->setData($accounts, 'bankaccounts');
    }

    public function setPayPorts($ports)
    {
        $this->setData($ports, 'payports');
    }

    public function setMethod($method)
    {
        $this->data['methods'][] = $method;
    }

    public function getMethods()
    {
        return $this->getData('methods');
    }

    public function export()
    {
        return [
            'data' => [
                'methods' => $this->getMethods(),
            ],
        ];
    }
}
