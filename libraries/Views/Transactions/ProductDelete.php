<?php

namespace packages\financial\Views\Transactions;

use packages\financial\TransactionProduct;
use packages\financial\Views\Form;

class ProductDelete extends Form
{
    public function setProduct(TransactionProduct $product)
    {
        $this->setData($product, 'product');
    }

    public function getProduct(): TransactionProduct
    {
        return $this->getData('product');
    }
}
