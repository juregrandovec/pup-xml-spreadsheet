<?php

namespace App\Service;

use Exception;

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
                if (ftp_get($ftpConnection, $localFileName, $serverFileName,)) {
                    return $localFileName;
                } else {
                    ftp_close($ftpConnection);
                    throw new Exception("FTP GET failed!");
                }

            } else {
                ftp_close($ftpConnection);
                throw new Exception("FTP login failed!");
            }
        }

        throw new Exception("FTP connection failed!");
    }
}