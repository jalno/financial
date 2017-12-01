<?php
namespace packages\financial\logs\transactions;
use \packages\base\{view, translator};
use \packages\userpanel\{logs\panel, logs};
class edit extends logs{
	public function getColor():string{
		return "circle-teal";
	}
	public function getIcon():string{
		return "fa fa-money";
	}
	public function buildFrontend(view $view){
		$parameters = $this->log->parameters;
		$oldData = $parameters['oldData'];
		$products = isset($oldData['products']) ? $oldData['products'] : [];
		unset($oldData['products']);
		if(!empty($oldData)){
			$panel = new panel('financial.logs.transaction.edit');
			$panel->icon = 'fa fa-external-link-square';
			$panel->size = 6;
			$panel->title = translator::trans('financial.logs.transaction.information');
			$html = '';
			if(isset($oldData['user'])){
				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.user").': </label>';
				$html .= '<div class="col-xs-8">'.$oldData['user']->getFullName().'</div>';
				$html .= "</div>";
				unset($oldData['user']);
			}
			if(isset($oldData['currency'])){
				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.currency").': </label>';
				$html .= '<div class="col-xs-8">'.$oldData['currency']->title.'</div>';
				$html .= "</div>";
				unset($oldData['currency']);
			}
			foreach($oldData as $field => $val){
				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.{$field}").': </label>';
				$html .= '<div class="col-xs-8">'.$val.'</div>';
				$html .= "</div>";
			}
			$panel->setHTML($html);
			$this->addPanel($panel);
		}

		if(!empty($products)){
			$panel = new panel('financial.logs.transaction.edit.products');
			$panel->icon = 'fa fa-external-link-square';
			$panel->size = 6;
			$panel->title = translator::trans('financial.logs.transaction.products');
			$html = '';
			$html = '<div class="table-responsive">';
			$html .= '<table class="table table-striped">';
			$html .= "<thead><tr>";
			$html .= "<th>#</th>";
			$html .= "<th>عنوان</th>";
			$html .= "<th>قیمت</th>";
			$html .= "</tr></thead>";
			$html .= "<tbody>";
			foreach($products as $product){
				$html .= "<tr><td>{$product->id}</th>";
				$html .= "<td>{$product->title}</td>";
				$html .= "<td><span class=\"ltr\">{$product->price}</span> {$product->currency->title}</td></tr>";
			}
			$html .= "</tbody></table></div>";

			$panel->setHTML($html);
			$this->addPanel($panel);
		}
	}
}
