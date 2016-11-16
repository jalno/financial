<?php
namespace themes\clipone\listeners\financial;
use \packages\base;
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\authorization;
use \themes\clipone\views\dashboard as view;
use \themes\clipone\views\dashboard\shortcut;
class dashboard{
	public function initialize(){
		$this->addShortcuts();
	}
	protected function addShortcuts(){
		if(authorization::is_accessed('transactions_list')){
			$shortcut = new shortcut("transactions");
			$shortcut->icon = 'fa fa-money';
			$shortcut->color = shortcut::bricky;
			$shortcut->title = translator::trans('shortcut.transactions.title');
			$shortcut->text = translator::trans('shortcut.transactions.text');
			$shortcut->setLink(translator::trans('shortcut.transactions.link'), userpanel\url('transactions'));
			view::addShortcut($shortcut);
		}
	}
}
