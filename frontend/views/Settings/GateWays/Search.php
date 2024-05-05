<?php

namespace themes\clipone\views\financial\Settings\GateWays;

use packages\base\Translator;
use packages\base\View\Error;
use packages\financial\Views\Settings\GateWays\Search as GateWaysListView;
use packages\userpanel;
use themes\clipone\Navigation;
use themes\clipone\Navigation\MenuItem;
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
        $this->setTitle(Translator::trans('settings.financial.gateways'));
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
                    'txt' => Translator::trans('settings.financial.gateways.add'),
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
            $gateways = new MenuItem('gateways');
            $gateways->setTitle(Translator::trans('settings.financial.gateways'));
            $gateways->setURL(userpanel\url('settings/financial/gateways'));
            $gateways->setIcon('fa fa-rss');
            $financial->addItem($gateways);
        }
    }

    public function setButtons()
    {
        $this->setButton('edit', $this->canEdit, [
            'title' => Translator::trans('edit'),
            'icon' => 'fa fa-edit',
            'classes' => ['btn', 'btn-xs', 'btn-warning'],
        ]);
        $this->setButton('delete', $this->canDel, [
            'title' => Translator::trans('delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky'],
        ]);
    }
}
