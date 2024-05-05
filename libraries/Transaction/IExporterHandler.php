<?php

namespace packages\financial\Transaction;

use packages\base\Response\File;

interface IExporterHandler
{
    public function export(array $transactions): File;
}
