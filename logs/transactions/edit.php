<?php
namespace packages\financial\logs\transactions;
use \packages\base\{view, translator};
use packages\financial\Currency;
use packages\financial\Transaction_product;
use \packages\userpanel\{logs\panel, logs, date};
use packages\userpanel\User;
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
				$user = $oldData['user'];
				if (is_numeric($user)) {
					$user = User::byId($oldData['user']);
				}

				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.user").': </label>';
				$html .= '<div class="col-xs-8">'.($user ? $user->getFullName() : "#{$oldData['user']}").'</div>';
				$html .= "</div>";

				unset($oldData['user']);
			}
			if(isset($oldData['currency'])){
				$currency = $oldData['currency'];
				if (is_numeric($currency)) {
					$currency = Currency::byId($oldData['currency']);
				}

				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.currency").': </label>';
				$html .= '<div class="col-xs-8">'.($currency ? $currency->title : "#{$oldData['currency']}").'</div>';
				$html .= "</div>";
				unset($oldData['currency']);
			}
			if(isset($oldData['expire_at'])){
				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.expire_at").': </label>';
				$html .= '<div class="col-xs-8 ltr">'.date::format("Y/m/d H:i:s", $oldData['expire_at']).'</div>';
				$html .= "</div>";
				unset($oldData['expire_at']);
			}
			if(isset($oldData['create_at'])){
				$html .= '<div class="form-group">';
				$html .= '<label class="col-xs-4 control-label">'.translator::trans("transaction.add.create_at").': </label>';
				$html .= '<div class="col-xs-8 ltr">'.date::format("Y/m/d H:i:s", $oldData['create_at']).'</div>';
				$html .= "</div>";
				unset($oldData['create_at']);
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
			foreach($products as $id => $data) {
				$product = Transaction_product::byId($id);

				$html .= "<tr><td>{$id}</th>";
				$html .= '<td>'.($data['title'] ?? $product->title).'</td>';
				$html .= '<td><span class="ltr">'.($data['price'] ?? $product->price).'</span> '.($product ? $product->currency->title : '').'</td></tr>';
			}
			$html .= "</tbody></table></div>";

			$panel->setHTML($html);
			$this->addPanel($panel);
		}
	}
}
