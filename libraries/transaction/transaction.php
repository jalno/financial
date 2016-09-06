<?php
namespace packages\financial;
use \packages\financial\transaction_product;
use \packages\base\db\dbObject;
class transaction extends dbObject{
	const unpaid = 0;
	const paid = 1;
	const refund = 2;
	const host = 1;
	const domain = 2;
	protected $dbTable = "financial_transactions";
	protected $primaryKey = "id";
	protected $dbFields = array(
		'id' => array('type' => 'int'),
        'user' => array('type' => 'int', 'required' => true),
        'title' => array('type' => 'text', 'required' => true),
        'price' => array('type' => 'int', 'required' => true),
		'create_at' => array('type' => 'int', 'required' => true),
		'expire_at' => array('type' => 'int'),
		'paid_at' => array('type' => 'int'),
		'status' => array('type' => 'int', 'required' => true)
    );
	protected $relations = array(
		'user' => array('hasOne', 'packages\\userpanel\\user', 'user'),
		'params' => array('hasMany', 'packages\\financial\\transactions_params', 'transaction'),
		'products' => array('hasMany', 'packages\\financial\\transaction_product', 'transaction')
	);
	protected $tmproduct = array();
	protected function addProduct($productdata){
		$product = new transaction_product($productdata);
		if ($this->isNew){
			$this->tmproduct[] = $product;
			return true;
		}else{
			$product->transaction = $this->id;
			return $product->save();
		}
	}
	public function save($data = null){
		if(($return = parent::save($data))){
			foreach($this->tmproduct as $product){
				$product->transaction = $this->id;
				$product->save();
			}
			$this->tmproduct = array();
		}
		return $return;
	}
}
