<?php

namespace packages\financial\Views\Transactions\Product;

use packages\financial\TransactionProduct;
use packages\financial\Views\Form;

class Config extends Form
{
    public function setProduct(TransactionProduct $product)
    {
        $this->setData($product, 'product');
    }

    protected function getProduct()
    {
        return $this->getData('product');
    }
}
