<?php

namespace App\Services;

use App\Services\Interfaces\FileDownloadService;
use Exception;
use FTP\Connection;

class FtpService implements FileDownloadService
{
    public function __construct(private $ftpDomain, private $ftpUsername, private $ftpPassword)
    {
    }

    /**
     * Opens FTP connection and downloads the file
     *
     * @throws Exception
     */
    public function downloadFileAsTmp(string $tmpFileName, string $serverFileName): string
    {
        $ftpConnection = ftp_connect($this->ftpDomain);

        if ($ftpConnection) {
            $login_result = ftp_login($ftpConnection, $this->ftpUsername, $this->ftpPassword);
            ftp_pasv($ftpConnection, true);

            if ($login_result) {

                return $this->downloadTmpFileFromFtpConnection($ftpConnection, $tmpFileName, $serverFileName);
            } else {
                ftp_close($ftpConnection);
                throw new Exception("FTP login failed!");
            }
        }
        throw new Exception("FTP connection failed!");
    }

    /**
     * @param Connection $ftpConnection
     * @param string $tmpFileName
     * @param string $serverFileName
     * @return string
     * @throws Exception
     */
    private function downloadTmpFileFromFtpConnection(Connection $ftpConnection, string $tmpFileName, string $serverFileName): string
    {
        if (ftp_get($ftpConnection, $tmpFileName, $serverFileName,)) {

            return $tmpFileName;
        } else {
            ftp_close($ftpConnection);
            throw new Exception("FTP GET failed!");
        }
    }
}