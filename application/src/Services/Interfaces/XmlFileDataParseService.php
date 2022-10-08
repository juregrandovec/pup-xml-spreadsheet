<?php

namespace App\Services\Interfaces;

use App\Classes\XmlFileDataParseSettings;

interface XmlFileDataParseService
{
    public function parseDataFromFile(XmlFileDataParseSettings $xmlXmlFileDataParseSettings): array;
}