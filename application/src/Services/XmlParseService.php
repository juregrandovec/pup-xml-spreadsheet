<?php

namespace App\Services;

class XmlParseService
{

    /**
     * @param string $fileName
     * @param $xmlParentElementName
     * @return array
     */
    public function getParsedDataFromXmlFile(string $fileName, $xmlParentElementName): array
    {
        $xml = simplexml_load_file($fileName, options: LIBXML_NOCDATA);
        $xmlItems = json_decode(json_encode($xml), true);

        return $this->parseXmlItemsIntoArray($xmlItems[$xmlParentElementName]);
    }

    /**
     * @param $xmlItems
     * @return array
     */
    public function parseXmlItemsIntoArray($xmlItems): array
    {
        $columnNamesData = array_keys($xmlItems[0]);
        $data = [$columnNamesData];

        foreach ($xmlItems as $xmlItem) {
            $data[] = $this->parseItemValues($xmlItem);
        }

        return $data;
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function parseItemValues(array $item): array
    {
        $itemValues = array_values($item);
        return $this->changeImproperItemValues($itemValues);
    }

    /**
     * @param mixed $itemValues
     * @return array
     */
    public function changeImproperItemValues(array $itemValues): array
    {
        foreach ($itemValues as &$itemValue) {
            if (is_array($itemValue)) {
                $itemValue = implode(', ', $itemValue);
            }
        }
        return $itemValues;
    }
}