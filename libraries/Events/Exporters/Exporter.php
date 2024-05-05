<?php
namespace packages\financial\Events\Exporters;
use packages\base\{Event, Exception};

class Exporter {
	private $name;
	private $handler;
	public function __construct(string $name) {
		$this->setName($name);
	}
	public function setName(string $name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	public function setHandler(string $handler) {
		if (!class_exists($handler)) {
			throw new Exception("exporter handler {$handler} not defined");
		}
		$this->handler = $handler;
	}
	public function getHandler() {
		return $this->handler;
	}
}
