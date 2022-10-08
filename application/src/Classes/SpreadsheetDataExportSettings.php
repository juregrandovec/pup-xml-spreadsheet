<?php

namespace App\Classes;

class SpreadsheetDataExportSettings
{
    public function __construct(public string $spreadsheetTitle, public ?string $existingSpreadsheetId)
    {
    }
}