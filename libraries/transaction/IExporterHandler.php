<?php
namespace packages\financial\Transaction;
use packages\base\response\File;

interface IExporterHandler {
	public function export(array $transactions): File;
}
