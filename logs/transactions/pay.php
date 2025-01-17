<?php
namespace packages\financial\logs\transactions;
use \packages\base\{view, translator};
use packages\financial\Contracts\ITransactionManager;
use \packages\userpanel\{logs\panel, logs};
use \packages\financial\transaction_pay;
use packages\financial\TransactionManager;

class pay extends logs{
	public function getColor():string{
		return "circle-green";
	}

	public function getIcon():string{
		return "fa fa-money";
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

	private ?ITransactionManager $transactionManager = null;

	private function getPayTitle(transaction_pay $pay): string
	{
		if ($pay->method == transaction_pay::payaccepted) {
			return translator::trans('pay.method.payaccepted', array(
				'acceptor' =>
				$this->log->user ? $this->log->user->getFullName() : 'سیستم'
			));
		}

		$transaction = $pay->transaction;
		if (!$transaction) {
			return $pay->method;
		}

		$paymentMethods = $this->getTransactionManager()->getPaymentMethods($transaction);
		if (!isset($paymentMethods[$pay->method])) {
			return $pay->method;
		}

		return $paymentMethods[$pay->method]->getPayTitle($pay);
	}

	private function getTransactionManager(): ITransactionManager
	{
		return $this->transactionManager ?: $this->transactionManager = TransactionManager::getInstance();
	}
}
