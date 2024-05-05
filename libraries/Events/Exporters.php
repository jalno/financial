<?php

namespace packages\financial\Events;

use packages\base\Event;
use packages\financial\Events\Exporters\Exporter;

class Exporters extends Event
{
    private $exporters = [];

    public function addExporter(Exporter $Exporter)
    {
        $this->exporters[$Exporter->getName()] = $Exporter;
    }

    public function getExporterNames()
    {
        return array_keys($this->exporters);
    }

    public function getByName(string $name)
    {
        return $this->exporters[$name] ?? null;
    }

    public function get()
    {
        return $this->exporters;
    }
}
