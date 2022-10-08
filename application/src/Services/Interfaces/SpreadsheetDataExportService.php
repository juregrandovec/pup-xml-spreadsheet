<?php

namespace App\Services\Interfaces;

use App\Classes\SpreadsheetDataExportResult;
use App\Classes\SpreadsheetDataExportSettings;

interface SpreadsheetDataExportService
{
    public function exportData(array $data, SpreadsheetDataExportSettings $dataExportSettings): SpreadsheetDataExportResult;
}