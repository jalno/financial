<?php
namespace packages\financial\Logs\Transactions;
use \packages\base\{View, Translator};
use \packages\userpanel\{Logs\Panel, Logs};
use \packages\financial\TransactionPay;
class Pay extends Logs{
	public function getColor():string{
		return "circle-green";
	}
	public function getIcon():string{
		return "fa fa-money";
	}
	private function getPayTitle(TransactionPay $pay){
		switch($pay->method){
			case(TransactionPay::credit):
				return Translator::trans('pay.method.credit');
			case(TransactionPay::banktransfer):
				return Translator::trans('pay.byBankTransfer');
			case(TransactionPay::onlinepay):
				return Translator::trans('pay.byPayOnline');
			case(TransactionPay::payaccepted):
				return Translator::trans('pay.method.payaccepted', array('acceptor' => $this->log->user->getFullName()));
		}
	}
	public function buildFrontend(View $view){
		$parameters = $this->log->parameters;
		$pay = $parameters['pay'];
		$currency = $parameters['currency'];

		$panel = new Panel('financial.logs.transaction.pay');
		$panel->icon = 'fa fa-external-link-square';
		$panel->size = 6;
		$panel->title = Translator::trans('financial.pay.information');
		$html = '';

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.Translator::trans("financial.pay.id").': </label>';
		$html .= '<div class="col-xs-8">#'.$pay->id.'</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.Translator::trans("pay.method").': </label>';
		$html .= '<div class="col-xs-8">'.$this->getPayTitle($pay).'</div>';
		$html .= "</div>";
		
		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.Translator::trans('financial.settings.currency.price').': </label>';
		$html .= '<div class="col-xs-8">'.$pay->price.' '.$currency->title.'</div>';
		$html .= "</div>";

		$panel->setHTML($html);
		$this->addPanel($panel);
	}
}
