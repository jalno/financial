<?php
namespace themes\clipone\views\financial\settings\gateways;

use themes\clipone\{Navigation, ViewTrait};
use packages\financial\views\settings\gateways\delete as DeleteView;

class Delete extends DeleteView {
	use viewTrait;

	public function __beforeLoad() {
		$this->setTitle(t("settings.financial.gateways.delete"));
		navigation::active("settings/financial/gateways");
	}
}
