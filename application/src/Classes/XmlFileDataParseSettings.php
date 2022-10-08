<?php

namespace App\Classes;

class XmlFileDataParseSettings
{
    public function __construct(public string $filename, public string $xmlParentElementName)
    {
    }
}