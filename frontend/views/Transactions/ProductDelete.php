<?php

namespace themes\clipone\Views\Transactions;

use packages\base\Translator;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class ProductDelete extends \packages\financial\Views\Transactions\ProductDelete
{
    use ViewTrait;
    use FormTrait;
    protected $product;

    public function __beforeLoad()
    {
        $this->product = $this->getProduct();
        $this->setTitle([
            Translator::trans('transaction.product.delete'),
            $this->product->id,
        ]);
        $this->setShortDescription(Translator::trans('transaction.product.delete'));
        $this->setNavigation();
    }

    private function setNavigation()
    {
        Navigation::active('transactions/list');
    }
}
