<?php
namespace packages\financial\events;
use packages\base\event;
use packages\financial\events\Exporters\Exporter;

class Exporters extends event{
	private $exporters = array();
	public function addExporter(Exporter $Exporter) {
		$this->exporters[$Exporter->getName()] = $Exporter;
	}
	public function getExporterNames() {
		return array_keys($this->exporters);
	}
	public function getByName(string $name) {
		return $this->exporters[$name] ?? null;
	}
	public function get() {
		return $this->exporters;
	}
}
