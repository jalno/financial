<?php

namespace themes\clipone\Views\Transactions\Product;

use packages\base\Translator;
use packages\financial\Views\Transactions\Product\Config as ConfigProduct;
use themes\clipone\Navigation;
use themes\clipone\Views\FormTrait;
use themes\clipone\ViewTrait;

class Config extends ConfigProduct
{
    use ViewTrait;
    use FormTrait;
    protected $product;

    public function __beforeLoad()
    {
        $this->product = $this->getProduct();
        $this->setTitle(t('transaction.product.configure'));
        $this->setNavigation();
        $this->setErrors();
    }

    private function setNavigation()
    {
        Navigation::active('transactions/list');
    }

    private function setErrors()
    {
        foreach ($this->product->getErrors() as $error) {
            $this->addError($error);
        }
    }
}
