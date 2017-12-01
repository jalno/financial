<?php
namespace packages\financial\logs\transactions;
use \packages\base\{view, translator};
use \packages\userpanel\{logs\panel, logs};
use \packages\financial\transaction_pay;
class pay extends logs{
	public function getColor():string{
		return "circle-green";
	}
	public function getIcon():string{
		return "fa fa-money";
	}
	private function getPayTitle(transaction_pay $pay){
		switch($pay->method){
			case(transaction_pay::credit):
				return translator::trans('pay.method.credit');
			case(transaction_pay::banktransfer):
				return translator::trans('pay.byBankTransfer');
			case(transaction_pay::onlinepay):
				return translator::trans('pay.byPayOnline');
			case(transaction_pay::payaccepted):
				return translator::trans('pay.method.payaccepted', array('acceptor' => $this->log->user->getFullName()));
		}
	}
	public function buildFrontend(view $view){
		$parameters = $this->log->parameters;
		$pay = $parameters['pay'];
		$currency = $parameters['currency'];

		$panel = new panel('financial.logs.transaction.pay');
		$panel->icon = 'fa fa-external-link-square';
		$panel->size = 6;
		$panel->title = translator::trans('financial.pay.information');
		$html = '';

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.translator::trans("financial.pay.id").': </label>';
		$html .= '<div class="col-xs-8">#'.$pay->id.'</div>';
		$html .= "</div>";

		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.translator::trans("pay.method").': </label>';
		$html .= '<div class="col-xs-8">'.$this->getPayTitle($pay).'</div>';
		$html .= "</div>";
		
		$html .= '<div class="form-group">';
		$html .= '<label class="col-xs-4 control-label">'.translator::trans('financial.settings.currency.price').': </label>';
		$html .= '<div class="col-xs-8">'.$pay->price.' '.$currency->title.'</div>';
		$html .= "</div>";

		$panel->setHTML($html);
		$this->addPanel($panel);
	}
}
