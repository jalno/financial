<?php

namespace themes\clipone\Views\Financial\Settings\Currencies;

use packages\base\View\Error;
use packages\financial\Views\Settings\Currencies\Search as CurrenciesListView;
use packages\userpanel;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Search extends CurrenciesListView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;

    public function __beforeLoad()
    {
        $this->setTitle(t('settings.financial.currencies'));
        $this->addBodyClass('financial-settings');
        $this->addBodyClass('settings-currencies');
        Navigation::active('settings/financial/currencies');
        $this->setButtons();
        if (empty($this->getDataList())) {
            $this->addNotFoundError();
        }
    }

    private function addNotFoundError()
    {
        $error = new Error();
        $error->setType(Error::NOTICE);
        $error->setCode('financial.settings.currency.notfound');
        if ($this->canAdd) {
            $error->setData([
                [
                    'type' => 'btn-success',
                    'txt' => t('settings.financial.currency.add'),
                    'link' => userpanel\url('settings/financial/currencies/add'),
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
            'classes' => ['btn', 'btn-xs', 'btn-teal'],
        ]);
        $this->setButton('delete', $this->canDel, [
            'title' => t('delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }
}
