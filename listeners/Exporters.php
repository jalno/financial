<?php
namespace packages\financial\listeners;

use packages\financial\{events\Exporters as Event, exporters\CSVExporter};

class Exporters {
	public function add(Event $e) {
		$exporter = new Event\Exporter("csv");
		$exporter->setHandler(CSVExporter::class);
		$e->addExporter($exporter);
	}	
}
