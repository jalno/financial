<?php

namespace packages\financial\Listeners;

use packages\financial\Events\Exporters as Event;
use packages\financial\Exporters\CSVExporter;

class Exporters
{
    public function add(Event $e)
    {
        $exporter = new Event\Exporter('csv');
        $exporter->setHandler(CSVExporter::class);
        $e->addExporter($exporter);
    }
}
