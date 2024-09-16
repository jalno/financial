<?php

namespace themes\clipone\Views\Financial\Settings\GateWays;

use packages\base\View\Error;
use packages\financial\Views\Settings\GateWays\Search as GateWaysListView;
use packages\userpanel;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Search extends GateWaysListView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;
    private $categories;

    public function __beforeLoad()
    {
        $this->setTitle(t('settings.financial.gateways'));
        Navigation::active('settings/financial/gateways');
        $this->setButtons();
        if (empty($this->getDataList())) {
            $this->addNotFoundError();
        }
    }

    private function addNotFoundError()
    {
        $error = new Error();
        $error->setType(Error::NOTICE);
        $error->setCode('financial.settings.payport.notfound');
        if ($this->canAdd) {
            $error->setData([
                [
                    'type' => 'btn-success',
                    'txt' => t('settings.financial.gateways.add'),
                    'link' => userpanel\url('settings/financial/gateways/add'),
                ],
            ], 'btns');
        }
        $this->addError($error);
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

    public function setButtons()
    {
        $this->setButton('edit', $this->canEdit, [
            'title' => t('edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-warning'],
        ]);
        $this->setButton('delete', $this->canDel, [
            'title' => t('delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }
}
