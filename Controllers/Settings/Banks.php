<?php

namespace packages\financial\Controllers\Settings;

use packages\base\NotFound;
use packages\base\Response;
use packages\financial\Authorization;
use packages\financial\Bank;
use packages\financial\Controller;
use packages\financial\View;
use themes\clipone\views\financial as Views;

class Banks extends Controller
{
    protected $authentication = true;

    public function search(): Response
    {
        Authorization::haveOrFail('settings_banks_search');
        $view = View::byName(Views\Settings\Banks\Search::class);
        $this->response->setView($view);
        $inputs = $this->checkinputs([
            'id' => [
                'type' => 'number',
                'optional' => true,
                'empty' => true,
            ],
            'title' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'status' => [
                'values' => [Bank::Active, Bank::Deactive],
                'optional' => true,
                'empty' => true,
            ],
            'comparison' => [
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ]);
        foreach (['id', 'title', 'status'] as $item) {
            if (isset($inputs[$item]) and '' == $inputs[$item]) {
                unset($inputs[$item]);
            }
        }
        $bank = new Bank();
        foreach (['id', 'title', 'status'] as $item) {
            if (isset($inputs[$item])) {
                $comparison = $inputs['comparison'];
                if (in_array($item, ['id', 'status'])) {
                    $comparison = 'equals';
                }
                $bank->where($item, $inputs[$item], $comparison);
            }
        }
        $bank->orderBy('id', 'DESC');
        $bank->pageLimit = $this->items_per_page;
        $banks = $bank->paginate($this->page);
        $this->total_pages = $bank->totalPages;
        $view->setDataList($banks);
        $view->setPaginate($this->page, $bank->totalCount, $this->items_per_page);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function store(): Response
    {
        Authorization::haveOrFail('settings_banks_add');
        $inputs = $this->checkinputs([
            'title' => [
                'type' => 'string',
            ],
        ]);
        $bank = new Bank($inputs);
        $bank->save();
        $this->response->setData($bank->toArray(), 'bank');
        $this->response->setStatus(true);

        return $this->response;
    }

    public function update(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_edit');
        if (!$bank = Bank::byId($data['bank'])) {
            throw new NotFound();
        }
        $inputs = $this->checkinputs([
            'title' => [
                'type' => 'string',
                'optional' => true,
            ],
            'status' => [
                'values' => [Bank::Active, Bank::Deactive],
                'optional' => true,
            ],
        ]);
        if (isset($inputs['title']) or isset($inputs['status'])) {
            $bank->save($inputs);
        }
        $this->response->setStatus(true);
        $this->response->setData($bank->toArray(), 'bank');

        return $this->response;
    }

    public function terminate(array $data): Response
    {
        Authorization::haveOrFail('settings_banks_delete');
        if (!$bank = Bank::byId($data['bank'])) {
            throw new NotFound();
        }
        $bank->delete();
        $this->response->setStatus(true);

        return $this->response;
    }
}
