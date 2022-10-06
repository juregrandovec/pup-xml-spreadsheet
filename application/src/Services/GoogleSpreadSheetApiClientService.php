<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\ValueRange;

class GoogleSpreadSheetApiClientService
{
    const INPUT_OPTION_RAW = 'RAW';
    const DEFAULT_TITLE = 'sheet1';
    protected Sheets $service;

    /**
     * @throws \Google\Exception
     * @throws Exception
     */
    public function __construct(string $googleCredentialsFilePath)
    {
        if (!file_exists($googleCredentialsFilePath)) {
            throw new Exception(sprintf("Google Api Credentials File not found! (%s)", $googleCredentialsFilePath));
        }

        $client = new Client();
        $client->setAuthConfig($googleCredentialsFilePath);
        $client->addScope(Drive::DRIVE);
        $this->service = new Sheets($client);
    }

    /**
     * @param $values
     * @param string $title
     * @return int
     */
    public function insertDataIntoANewSpreadsheet($values, string $title = self::DEFAULT_TITLE): array
    {
        try {
            $spreadsheet = $this->createNewSpreadsheet($title);
            return [$spreadsheet->spreadsheetId, $this->updateSpreadsheetValues($spreadsheet->spreadsheetId, $values, $title, self::INPUT_OPTION_RAW)];

        } catch (Exception $e) {
            // TODO(developer) - handle error appropriately
            echo 'Message: ' . $e->getMessage();
            return 0;
        }
    }

    /**
     * @param string $spreadsheetId
     * @param array $values
     * @param string $range
     * @param string $valueInputOption
     * @return int
     */
    function updateSpreadsheetValues(string $spreadsheetId, array $values, string $range, string $valueInputOption): int
    {
        try {
            $body = new ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => $valueInputOption
            ];

            $result = $this->service->spreadsheets_values->update($spreadsheetId, $range,
                $body, $params);

            return $result->getUpdatedCells();

        } catch (Exception $e) {
            // TODO(developer) - handle error appropriately
            echo 'Message: ' . $e->getMessage();
            return 0;
        }
    }

    /**
     * @param string $title
     * @return Spreadsheet
     */
    public function createNewSpreadsheet(string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);

        return $this->service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
    }
}