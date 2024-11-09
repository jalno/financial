<?php

namespace packages\financial\Controllers\Settings;

use packages\base\DB\DuplicateRecord;
use packages\base\DB\Parenthesis;
use packages\base\Events;
use packages\base\Http;
use packages\base\InputValidation;
use packages\base\NotFound;
use packages\base\Views\FormError;
use packages\financial\Authorization;
use packages\financial\Bank\Account as BankAccount;
use packages\financial\Controller;
use packages\financial\Currency;
use packages\financial\Events\GateWays as GateWaysEvent;
use packages\financial\PayPort as GateWay;
use packages\financial\View;
use themes\clipone\Views\Financial\Settings\GateWays\Add;
use themes\clipone\Views\Financial\Settings\GateWays\Delete;
use themes\clipone\Views\Financial\Settings\GateWays\Edit;
use themes\clipone\Views\Financial\Settings\GateWays\Search;
use packages\userpanel;

class GateWays extends Controller
{
    protected $authentication = true;

    public function listgateways()
    {
        Authorization::haveOrFail('settings_gateways_search');
        $view = View::byName(Search::class);
        $gateways = new GateWaysEvent();
        Events::trigger($gateways);
        $gateway = new GateWay();
        $inputsRules = [
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
            'gateway' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'account' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'status' => [
                'type' => 'number',
                'optional' => true,
                'empty' => true,
            ],
            'word' => [
                'type' => 'string',
                'optional' => true,
                'empty' => true,
            ],
            'comparison' => [
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ];
        $this->response->setStatus(true);
        try {
            $inputs = $this->checkinputs($inputsRules);
            if (isset($inputs['status']) and 0 != $inputs['status']) {
                if (!in_array($inputs['status'], [GateWay::active, GateWay::deactive])) {
                    throw new InputValidation('status');
                }
            }
            if (isset($inputs['gateway']) and $inputs['gateway']) {
                if (!in_array($inputs['gateway'], $gateways->getGatewayNames())) {
                    throw new InputValidation('gateway');
                }
            }
            if (isset($inputs['account']) and $inputs['account']) {
                $bankaccount = new BankAccount();
                $bankaccount->where('status', BankAccount::Active);
                $bankaccount->where('id', $inputs['account']);
                if (!$bankaccount->has()) {
                    throw new InputValidation('account');
                }
            }

            foreach (['id', 'title', 'gateway', 'account', 'status'] as $item) {
                if (isset($inputs[$item]) and $inputs[$item]) {
                    $comparison = $inputs['comparison'];
                    if (in_array($item, ['id', 'gateway', 'account', 'status'])) {
                        $comparison = 'equals';
                        if ('gateway' == $item) {
                            $inputs[$item] = $gateways->getByName($inputs[$item]);
                        }
                    }
                    $gateway->where($item, $inputs[$item], $comparison);
                }
            }
            if (isset($inputs['word']) and $inputs['word']) {
                $parenthesis = new Parenthesis();
                foreach (['title'] as $item) {
                    if (!isset($inputs[$item]) or !$inputs[$item]) {
                        $parenthesis->where('financial_gateways.'.$item, $inputs['word'], $inputs['comparison'], 'OR');
                    }
                }
                $gateway->where($parenthesis);
            }
        } catch (InputValidation $error) {
            $view->setFormError(FormError::fromException($error));
            $this->response->setStatus(false);
        }
        $view->setDataForm($this->inputsvalue($inputsRules));
        $gateway->orderBy('id', 'ASC');
        $gateway->pageLimit = $this->items_per_page;
        $items = $gateway->paginate($this->page);
        $view->setPaginate($this->page, $gateway->totalCount, $this->items_per_page);
        $view->setDataList($items);
        $view->setGateways($gateways);
        $this->response->setView($view);

        return $this->response;
    }

    public function add()
    {
        Authorization::haveOrFail('settings_gateways_add');
        $view = View::byName(Add::class);
        $gateways = new GateWaysEvent();
        Events::trigger($gateways);
        $view->setGateways($gateways);
        $view->setCurrencies(Currency::get());
        if (HTTP::is_post()) {
            $inputsRules = [
                'title' => [
                    'type' => 'string',
                ],
                'gateway' => [
                    'type' => 'string',
                    'values' => $gateways->getGatewayNames(),
                ],
                'account' => [
                    'type' => 'number',
                    'optional' => true,
                    'empty' => true,
                ],
                'status' => [
                    'type' => 'number',
                    'values' => [GateWay::active, GateWay::deactive],
                ],
                'currency' => [
                    'optional' => true,
                ],
            ];
            $this->response->setStatus(true);
            try {
                $inputs = $this->checkinputs($inputsRules);
                if (isset($inputs['account'])) {
                    if ($inputs['account']) {
                        $bankaccount = new BankAccount();
                        $bankaccount->where('status', BankAccount::Active);
                        $bankaccount->where('id', $inputs['account']);
                        if (!$bankaccount->has()) {
                            throw new InputValidation('account');
                        }
                    } else {
                        unset($inputs['account']);
                    }
                }
                if (isset($inputs['currency'])) {
                    if ($inputs['currency']) {
                        if (!is_array($inputs['currency'])) {
                            throw new InputValidation('currency');
                        }
                    } else {
                        unset($inputs['currency']);
                    }
                }
                if (isset($inputs['currency'])) {
                    foreach ($inputs['currency'] as $key => $currency) {
                        if (!Currency::byId($currency)) {
                            throw new InputValidation("currency[{$key}]");
                        }
                    }
                }
                $gateway = $gateways->getByName($inputs['gateway']);
                if ($GRules = $gateway->getInputs()) {
                    $GRules = $inputsRules = array_merge($inputsRules, $GRules);
                    $ginputs = $this->checkinputs($GRules);
                }
                if ($GRules = $gateway->getInputs()) {
                    $gateway->callController($ginputs);
                }
                $gatewayObj = new GateWay();
                $gatewayObj->title = $inputs['title'];
                if (isset($inputs['account'])) {
                    $gatewayObj->account = $inputs['account'];
                }
                $gatewayObj->controller = $gateway->getHandler();
                $gatewayObj->status = $inputs['status'];
                foreach ($gateway->getInputs() as $input) {
                    if (isset($ginputs[$input['name']])) {
                        $gatewayObj->setParam($input['name'], $ginputs[$input['name']]);
                    }
                }
                $gatewayObj->save();
                if (isset($inputs['currency'])) {
                    foreach ($inputs['currency'] as $currency) {
                        $gatewayObj->setCurrency($currency);
                    }
                }
                $this->response->setStatus(true);
                $this->response->Go(userpanel\url('settings/financial/gateways/edit/'.$gatewayObj->id));
            } catch (InputValidation $error) {
                $view->setFormError(FormError::fromException($error));
                $this->response->setStatus(false);
            } catch (DuplicateRecord $error) {
                $view->setFormError(FormError::fromException($error));
                $this->response->setStatus(false);
            }
            $view->setDataForm($this->inputsvalue($inputsRules));
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    public function delete($data)
    {
        Authorization::haveOrFail('settings_gateways_delete');
        $gateway = (new GateWay())->byID($data['gateway']);
        if (!$gateway) {
            throw new NotFound();
        }
        $view = View::byName(Delete::class);
        $view->setGateway($gateway);
        if (HTTP::is_post()) {
            $gateway->delete();

            $this->response->setStatus(true);
            $this->response->Go(userpanel\url('settings/financial/gateways'));
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }

    public function edit($data)
    {
        Authorization::haveOrFail('settings_gateways_edit');
        $gatewayObj = (new GateWay())->byID($data['gateway']);
        if (!$gatewayObj) {
            throw new NotFound();
        }
        $view = View::byName(Edit::class);
        $gateways = new GateWaysEvent();
        Events::trigger($gateways);
        $view->setGateways($gateways->get());
        $view->setGateway($gatewayObj);
        $view->setCurrencies(Currency::get());
        if (HTTP::is_post()) {
            $inputsRules = [
                'title' => [
                    'type' => 'string',
                    'optional' => true,
                ],
                'gateway' => [
                    'type' => 'string',
                    'values' => $gateways->getGatewayNames(),
                    'optional' => true,
                ],
                'account' => [
                    'type' => 'number',
                    'optional' => true,
                    'empty' => true,
                ],
                'status' => [
                    'type' => 'number',
                    'values' => [GateWay::active, GateWay::deactive],
                    'optional' => true,
                ],
                'currency' => [
                    'optional' => true,
                ],
            ];
            $this->response->setStatus(true);
            try {
                $inputs = $this->checkinputs($inputsRules);
                if (isset($inputs['account'])) {
                    if ($inputs['account']) {
                        $bankaccount = new BankAccount();
                        $bankaccount->where('status', BankAccount::Active);
                        $bankaccount->where('id', $inputs['account']);
                        if (!$bankaccount->has()) {
                            throw new InputValidation('account');
                        }
                    } else {
                        unset($inputs['gateway']);
                    }
                }
                if (isset($inputs['currency'])) {
                    if ($inputs['currency']) {
                        if (!is_array($inputs['currency'])) {
                            throw new InputValidation('currency');
                        }
                    } else {
                        unset($inputs['currency']);
                    }
                }
                if (isset($inputs['currency'])) {
                    foreach ($inputs['currency'] as $key => $currency) {
                        if (!Currency::byId($currency)) {
                            throw new InputValidation("currency[{$key}]");
                        }
                    }
                }
                if (isset($inputs['gateway'])) {
                    if ($inputs['gateway']) {
                        $gateway = $gateways->getByName($inputs['gateway']);
                        if ($GRules = $gateway->getInputs()) {
                            $GRules = $inputsRules = array_merge($inputsRules, $GRules);
                            $ginputs = $this->checkinputs($GRules);
                        }
                        if ($GRules = $gateway->getInputs()) {
                            $gateway->callController($ginputs);
                        }
                    } else {
                        unset($inputs['gateway']);
                    }
                }
                if (isset($inputs['title'])) {
                    $gatewayObj->title = $inputs['title'];
                }
                if (isset($inputs['account'])) {
                    $gatewayObj->account = $inputs['account'];
                }
                if (isset($inputs['gateway'])) {
                    $gatewayObj->controller = $gateway->getHandler();
                }
                if (isset($inputs['status'])) {
                    $gatewayObj->status = $inputs['status'];
                }
                if (isset($inputs['gateway'])) {
                    foreach ($gateway->getInputs() as $input) {
                        if (isset($ginputs[$input['name']])) {
                            $gatewayObj->setParam($input['name'], $ginputs[$input['name']]);
                        }
                    }
                }

                if (isset($inputs['currency'])) {
                    foreach ($gatewayObj->getCurrencies() as $currency) {
                        if (($key = array_search($currency['currency'], $inputs['currency'])) !== false) {
                            unset($inputs['currency'][$key]);
                        } else {
                            $gatewayObj->deleteCurrency($currency['currency']);
                        }
                    }
                    foreach ($inputs['currency'] as $key => $currency) {
                        $gatewayObj->setCurrency($currency);
                    }
                }
                $gatewayObj->save();
                $this->response->setStatus(true);
            } catch (InputValidation $error) {
                $view->setFormError(FormError::fromException($error));
                $this->response->setStatus(false);
            } catch (DuplicateRecord $error) {
                $view->setFormError(FormError::fromException($error));
                $this->response->setStatus(false);
            }
            $view->setDataForm($this->inputsvalue($inputsRules));
        } else {
            $this->response->setStatus(true);
        }
        $this->response->setView($view);

        return $this->response;
    }
}
