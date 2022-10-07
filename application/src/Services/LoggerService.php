<?php

namespace App\Services;

class LoggerService
{
    const LOG_FILE_NAME = './storage/logs/log.log';
    const LOG_FILE_DIRECTORY = './storage/logs';
    const LOG_TYPE_ERROR = 'ERROR';
    const LOG_DATE_FORMAT = 'Y-m-d h:i:s';

    /**
     * @param string $text
     * @return void
     */
    public function error(string $text): void
    {
        $fp = $this->openLogFile();
        fwrite($fp, $this->getLogMessage($text, self::LOG_TYPE_ERROR));
        fclose($fp);
    }

    /**
     * @return false|resource
     */
    private function openLogFile()
    {
        if (!file_exists(self::LOG_FILE_DIRECTORY)) {
            mkdir(self::LOG_FILE_DIRECTORY);
        }
        return fopen(self::LOG_FILE_NAME, 'a');
    }

    /**
     * @param string $text
     * @param $logType
     * @return string
     */
    private function getLogMessage(string $text, $logType): string
    {
        $formattedTime = date(self::LOG_DATE_FORMAT);
        return sprintf("%s - %s : %s\n", $formattedTime, $logType, $text);
    }
}