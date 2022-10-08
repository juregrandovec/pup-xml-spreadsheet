<?php

namespace App\Services\Interfaces;

interface FileDownloadService
{
    public function downloadFileAsTmp(string $tmpFileName, string $serverFileName);
}