<?php
namespace packages\financial;
use \packages\base\db\dbObject;

use payport\GatewayException;
use payport\VerificationException;
use payport\AlreadyVerified;
class payport extends dbObject{
	const active = 1;
	const deactive = 0;
	protected $dbTable = "financial_payports";
	protected $primaryKey = "id";
	private $controllerClass;
	protected $dbFields = array(
        'title' => array('type' => 'text', 'required' => true),
        'controller' => array('type' => 'text', 'required' => true),
		'status' => array('type' => 'int', 'required' => true)
    );
	public function getController(){
		if($this->controllerClass){
			return $this->controllerClass;
		}
		if(class_exists($this->controller)){
			$this->controllerClass = new $this->controller();
			return $this->controllerClass;
		}
		return false;
	}
	public function PaymentRequest($price, transaction $transaction, $ip = null){
		$pay = new payport_pay();
		$pay->price = $price;
		$pay->payport = $this->id;
		$pay->transaction = $transaction->id;
		if($ip){
			$pay->ip = $ip;
		}
		$pay->save();
		$controller = $this->getController();
		$redirect = $controller->PaymentRequest($pay);
		return $redirect;
	}
	public function PaymentVerification(payport_pay $pay){
		try{
			$controller = $this->getController();
			$newstatus = $controller->PaymentVerification($pay);
			$pay->status = $newstatus;
			$pay->save();
			return $pay->status;
		}catch(GatewayException $e){
			$pay->status = payport_pay::failed;
			$pay->save();
			throw $e;
		}catch(VerificationException $e){
			$pay->status = payport_pay::failed;
			$pay->save();
			throw $e;
		}
	}
}
