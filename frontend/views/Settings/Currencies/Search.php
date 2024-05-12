<?php

namespace themes\clipone\Views\Financial\Settings\Currencies;

use packages\base\Translator;
use packages\base\View\Error;
use packages\financial\Views\Settings\Currencies\Search as CurrenciesListView;
use packages\userpanel;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
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
        $this->setTitle(Translator::trans('settings.financial.currencies'));
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
                    'txt' => Translator::trans('settings.financial.currency.add'),
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
                'title' => Translator::trans('search.comparison.contains'),
                'value' => 'contains',
            ],
            [
                'title' => Translator::trans('search.comparison.equals'),
                'value' => 'equals',
            ],
            [
                'title' => Translator::trans('search.comparison.startswith'),
                'value' => 'startswith',
            ],
        ];
    }

    public static function onSourceLoad()
    {
        parent::onSourceLoad();
        if (parent::$navigation) {
            $settings = Navigation::getByName('settings');
            if (!$financial = Navigation::getByName('settings/financial')) {
                $financial = new MenuItem('financial');
                $financial->setTitle(Translator::trans('settings.financial'));
                $financial->setIcon('fa fa-money');
                if ($settings) {
                    $settings->addItem($financial);
                }
            }
            $currencies = new MenuItem('currencies');
            $currencies->setTitle(Translator::trans('settings.financial.currencies'));
            $currencies->setURL(userpanel\url('settings/financial/currencies'));
            $currencies->setIcon('fa fa-usd');
            $financial->addItem($currencies);
        }
    }

    public function setButtons()
    {
        $this->setButton('edit', $this->canEdit, [
            'title' => Translator::trans('edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-teal'],
        ]);
        $this->setButton('delete', $this->canDel, [
            'title' => Translator::trans('delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }
}
