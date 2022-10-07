<?php

namespace App\Services;

use Exception;
use FTP\Connection;

class FtpService
{
    public function __construct(private $ftpDomain, private $ftpUsername, private $ftpPassword)
    {
    }

    /**
     * Opens FTP connection and downloads the file
     *
     * @throws Exception
     */
    public function getFileFromFtp(string $localFileName, string $serverFileName): string
    {
        $ftpConnection = ftp_connect($this->ftpDomain);

        if ($ftpConnection) {
            $login_result = ftp_login($ftpConnection, $this->ftpUsername, $this->ftpPassword);
            ftp_pasv($ftpConnection, true);

            if ($login_result) {

                return $this->downloadTmpFileFromFtpConnection($ftpConnection, $localFileName, $serverFileName);
            } else {
                ftp_close($ftpConnection);
                throw new Exception("FTP login failed!");
            }
        }
        throw new Exception("FTP connection failed!");
    }

    /**
     * @param Connection $ftpConnection
     * @param string $localFileName
     * @param string $serverFileName
     * @return string
     * @throws Exception
     */
    private function downloadTmpFileFromFtpConnection(Connection $ftpConnection, string $localFileName, string $serverFileName): string
    {
        if (ftp_get($ftpConnection, $localFileName, $serverFileName,)) {

            return $localFileName;
        } else {
            ftp_close($ftpConnection);
            throw new Exception("FTP GET failed!");
        }
    }
}