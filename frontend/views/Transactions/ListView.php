<?php

namespace themes\clipone\Views\Transactions;

use packages\base\DB\Parenthesis;
use packages\base\Packages;
use packages\base\View\Error;
use packages\financial\Authentication;
use packages\financial\Authorization;
use packages\financial\Bank\Account;
use packages\financial\Currency;
use packages\financial\Transaction;
use packages\financial\TransactionProduct;
use packages\financial\Views\Transactions\ListView as TransactionsListView;
use packages\userpanel;
use packages\userpanel\Date;
use packages\userpanel\User;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\Views\TransactionTrait;
use themes\clipone\ViewTrait;

class ListView extends TransactionsListView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;
    use TransactionTrait;

    protected $multiuser = false;
    protected $canRefund = false;
    protected $canAccept = false;
    protected $user;
    protected ?User $selectedUserForRefund = null;
    private $exporters = [];

    public function __beforeLoad()
    {
        $this->setTitle([
            t('transactions'),
            t('list'),
        ]);
        $this->setButtons();
        $this->multiuser = (bool) Authorization::childrenTypes();
        $this->setDates();
        Navigation::active('transactions/list');
        if (empty($this->getTransactions())) {
            $this->addNotFoundError();
        }
        $this->canRefund = Authorization::is_accessed('transactions_refund_add');
        $this->canAccept = Authorization::is_accessed('transactions_pay_accept');

        $this->initFormData();
    }

    public static function onSourceLoad()
    {
        parent::onSourceLoad();
        if (self::$navigation) {
            $item = new MenuItem('transactions');
            $item->setTitle(t('packages.financial.transactions'));
            $item->setURL(userpanel\url('transactions'));
            $item->setIcon('fa fa-money');
            Navigation::addItem($item);
            if (Packages::package('dakhl')) {
                $invoices = Navigation::getByName('invoices');
                $bankAccounts = Navigation::getByName('bankAccounts');
                if ($invoices or $bankAccounts) {
                    $dakhl = new MenuItem('dakhl');
                    $dakhl->setTitle('دخل');
                    $dakhl->setIcon('fa fa-tachometer');
                    Navigation::addItem($dakhl);
                    if ($invoices) {
                        Navigation::removeItem($invoices);
                        $dakhl->addItem($invoices);
                    }
                    if ($bankAccounts) {
                        Navigation::removeItem($bankAccounts);
                        $dakhl->addItem($bankAccounts);
                    }
                }
            }
        }
    }

    public function setButtons()
    {
        $this->setButton('transactions_view', $this->canView, [
            'title' => t('packages.financial.view'),
            'icon' => 'fa fa-files-o',
            'classes' => ['btn', 'btn-xs', 'btn-green'],
        ]);
        $this->setButton('transactions_edit', $this->canEdit, [
            'title' => t('packages.financial.edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-teal'],
        ]);
        $this->setButton('transactions_delete', $this->canDel, [
            'title' => t('packages.financial.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }

    public function setDates()
    {
        foreach ($this->dataList as $key => $data) {
            $this->dataList[$key]->create_at = Date::format('Y/m/d H:i:s', $data->create_at);
        }
    }

    public function getComparisonsForSelect()
    {
        return [
            [
                'title' => t('search.comparison.contains'),
                'value' => 'contains',
            ],
            [
                'title' => t('search.comparison.equals'),
                'value' => 'equals',
            ],
            [
                'title' => t('search.comparison.startswith'),
                'value' => 'startswith',
            ],
        ];
    }

    public function setExporters(array $exporters)
    {
        $this->exporters = $exporters;
    }

    public function getCheckoutLimits(User $user): array
    {
        $limits = Transaction::getCheckoutLimits($user->id);

        if (isset($limits['period'])) {
            $limits['last_time'] = $user->option('financial_last_checkout_time');
        }

        return $limits;
    }

    protected function getStatusForSelect(): array
    {
        return [
            [
                'title' => '',
                'value' => '',
            ],
            [
                'title' => t('transaction.unpaid'),
                'value' => Transaction::UNPAID,
            ],
            [
                'title' => t('transaction.pending'),
                'value' => Transaction::PENDING,
            ],
            [
                'title' => t('transaction.paid'),
                'value' => Transaction::PAID,
            ],
            [
                'title' => t('transaction.refund'),
                'value' => Transaction::REFUND,
            ],
            [
                'title' => t('transaction.status.expired'),
                'value' => Transaction::EXPIRED,
            ],
        ];
    }

    protected function getBanksAccountForSelect(): array
    {
        $types = Authorization::childrenTypes();
        $accounts = [];
        $account = new Account();
        $account->with('user');
        $account->with('bank');
        if ($types) {
            $account->where('userpanel_users.type', $types, 'IN');
        } else {
            $account->where('financial_banks_accounts.user_id', Authentication::getID());
        }
        $account->where('financial_banks_accounts.status', Account::Active);
        foreach ($account->get(null, ['financial_banks_accounts.*', 'userpanel_users.*', 'financial_banks.*']) as $account) {
            if ($types) {
                $item = [
                    'title' => "{$account->bank->title} - {$account->user->getFullName()} [{$account->cart}]",
                    'value' => $account->id,
                ];
            } else {
                $item = [
                    'title' => "{$account->bank->title} [{$account->cart}]",
                    'value' => $account->id,
                ];
            }
            $item['data'] = [
                'user' => $account->user_id,
            ];
            $accounts[] = $item;
        }

        return $accounts;
    }

    protected function hasRefundTransaction(): bool
    {
        $types = Authorization::childrenTypes();
        $anonymous = Authorization::is_accessed('transactions_anonymous');
        $transaction = new Transaction();
        $transaction->join(TransactionProduct::class, null, 'INNER', 'transaction');
        if ($anonymous) {
            $transaction->join(User::class, 'user', 'LEFT');
            if ($types) {
                $parenthesis = new Parenthesis();
                $parenthesis->where('userpanel_users.type', $types, 'in');
                $parenthesis->orWhere('financial_transactions.user', null, 'is');
                $transaction->where($parenthesis);
            } else {
                $transaction->where('financial_transactions.user', null, 'is');
            }
        } else {
            $transaction->join(User::class, 'user', 'INNER');
            if ($types) {
                $transaction->where('userpanel_users.type', $types, 'in');
            } else {
                $transaction->where('financial_transactions.user', Authentication::getID());
            }
        }
        $transaction->where('financial_transactions.status', Transaction::unpaid);
        $transaction->where('financial_transactions_products.method', TransactionProduct::refund);

        return $transaction->has();
    }

    protected function getExportOptionsForSelect()
    {
        $options = [];
        foreach ($this->exporters as $exporter) {
            $options[] = [
                'title' => t("packages.financial.export.{$exporter->getName()}"),
                'value' => $exporter->getName(),
                'data' => [
                    'refund' => true,
                ],
            ];
        }

        return $options;
    }

    private function addNotFoundError()
    {
        $error = new Error();
        $error->setType(Error::NOTICE);
        $error->setCode('financial.transaction.notfound');
        $btns = [];
        if (Packages::package('ticketing')) {
            $btns[] = [
                'type' => 'btn-teal',
                'txt' => t('ticketing.add'),
                'link' => userpanel\url('ticketing/new'),
            ];
        }
        if ($this->canAdd) {
            $btns[] = [
                'type' => 'btn-success',
                'txt' => t('financial.transaction.add'),
                'link' => userpanel\url('transactions/new'),
            ];
        }
        if ($this->canAddingCredit) {
            $btns[] = [
                'type' => 'btn-success',
                'txt' => t('transaction.adding_credit'),
                'link' => userpanel\url('transactions/addingcredit'),
            ];
        }
        $error->setData($btns, 'btns');
        $this->addError($error);
    }

    private function initFormData()
    {
        if ($this->canRefund) {
            $this->user = Authentication::getUser();
            $this->user->currency = Currency::getDefault($this->user);
            if ($this->multiuser) {
                $user = $this->getDataForm('refund_user');
                if ($user) {
                    $user = User::byId($user);
                    if ($user) {
                        $this->setDataForm($user->getFullName(), 'refund_user_name');
                        $user->currency = Currency::getDefault($user);
                        $this->selectedUserForRefund = $user;
                    }
                } else {
                    $this->setDataForm($this->user->id, 'refund_user');
                    $this->setDataForm($this->user->getFullName(), 'refund_user_name');
                }

                if (!$this->selectedUserForRefund) {
                    $this->selectedUserForRefund = $this->user;
                }
            }
        }
    }
}
