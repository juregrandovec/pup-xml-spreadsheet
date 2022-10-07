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
    protected Sheets $service;

    /**
     * @param string $googleCredentialsFilePath
     * @throws \Google\Exception
     * @throws Exception
     */
    public function __construct(string $googleCredentialsFilePath)
    {
        if (!file_exists($googleCredentialsFilePath)) {
            throw new Exception(sprintf("Google Api Credentials File not found! (%s)", $googleCredentialsFilePath));
        }

        $this->service = new Sheets($this->getClient($googleCredentialsFilePath));
    }

    /**
     * @param array $values
     * @param string $title
     * @param string|null $existingSpreadsheetId
     * @return array|null
     * @throws \Google\Exception
     */
    public function insertDataIntoASpreadsheet(array $values, string $title, ?string $existingSpreadsheetId): ?array
    {
        $spreadsheetId = $existingSpreadsheetId ?: $this->createNewSpreadsheet($title)->spreadsheetId;

        return [$spreadsheetId, $this->updateSpreadsheetValues($spreadsheetId, $values, $title, self::INPUT_OPTION_RAW)];
    }

    /**
     * @param string $spreadsheetId
     * @param array $values
     * @param string $range
     * @param string $valueInputOption
     * @return int
     * @throws \Google\Exception
     */
    private function updateSpreadsheetValues(string $spreadsheetId, array $values, string $range, string $valueInputOption): int
    {
        $body = new ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => $valueInputOption
        ];

        $result = $this->service->spreadsheets_values->update($spreadsheetId, $range,
            $body, $params);

        return $result->getUpdatedCells();
    }

    /**
     * @param string $title
     * @return Spreadsheet
     * @throws \Google\Exception
     */
    private function createNewSpreadsheet(string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet([
            'properties' => [
                'title' => $title,
            ]
        ]);

        return $this->service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
    }

    /**
     * @param string $googleCredentialsFilePath
     * @return Client
     * @throws \Google\Exception
     */
    private function getClient(string $googleCredentialsFilePath): Client
    {
        $client = new Client();
        $client->setAuthConfig($googleCredentialsFilePath);
        $client->addScope(Drive::DRIVE);
        return $client;
    }
}