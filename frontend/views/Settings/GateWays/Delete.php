<?php
namespace themes\clipone\views\financial\Settings\GateWays;

use themes\clipone\{Navigation, ViewTrait};
use packages\financial\Views\Settings\GateWays\Delete as DeleteView;

class Delete extends DeleteView {
	use ViewTrait;

	public function __beforeLoad() {
		$this->setTitle(t("settings.financial.gateways.delete"));
		Navigation::active("settings/financial/gateways");
	}
}
