<?php

namespace App\Classes;

class SpreadsheetDataExportResult
{
    public function __construct(public string $spreadsheetId, public int $exportedCells)
    {
    }
}