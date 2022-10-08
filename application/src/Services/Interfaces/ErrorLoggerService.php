<?php

namespace App\Services\Interfaces;

interface ErrorLoggerService
{
    public function error(string $errorText): void;
}