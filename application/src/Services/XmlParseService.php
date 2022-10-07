<?php

namespace App\Services;

use Exception;

class XmlParseService
{
    /**
     * @param string $fileName
     * @param $xmlParentElementName
     * @return array|null
     * @throws Exception
     */
    public function getParsedDataFromXmlFile(string $fileName, $xmlParentElementName): ?array
    {
        $xml = simplexml_load_file($fileName, options: LIBXML_NOCDATA);
        if (!$xml) {
            throw new Exception("Error while reading data from XML file");
        }

        $xmlItems = json_decode(json_encode($xml), true);

        if (!isset($xmlItems[$xmlParentElementName])) {
            throw new Exception("XML element not found");
        }

        return $this->parseXmlItemsIntoArray($xmlItems[$xmlParentElementName]);
    }

    /**
     * @param array
     * @return array
     */
    private function parseXmlItemsIntoArray(array $xmlItems): array
    {
        $columnNames = array_keys($xmlItems[0]);
        $data = [$columnNames];

        foreach ($xmlItems as $xmlItem) {
            $data[] = $this->parseItemValues($xmlItem);
        }

        return $data;
    }

    /**
     * @param array $item
     * @return array
     */
    private function parseItemValues(array $item): array
    {
        $itemValues = array_values($item);
        return $this->changeImproperItemValues($itemValues);
    }

    /**
     * @param array $itemValues
     * @return array
     */
    private function changeImproperItemValues(array $itemValues): array
    {
        foreach ($itemValues as &$itemValue) {
            if (is_array($itemValue)) {
                $itemValue = implode(', ', $itemValue);
            }
        }
        return $itemValues;
    }
}