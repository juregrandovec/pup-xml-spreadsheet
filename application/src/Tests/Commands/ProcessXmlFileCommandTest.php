<?php

namespace App\Tests\Commands;

use Acme\Tester;
use App\Commands\ProcessXmlFileCommand;
use App\Services\FileErrorLoggerService;
use App\Services\FtpService;
use App\Services\GoogleSpreadsheetDataExportService;
use App\Services\SimpleXmlFileDataParseService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tester\TesterTrait;
use Symfony\Component\Dotenv\Dotenv;

class ProcessXmlFileCommandTest extends TestCase
{
    use TesterTrait;

    private Command $command;
    private CommandTester $tester;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $dotEnv = new Dotenv();
        $dotEnv->loadEnv(__DIR__ . '/../../../.env.testing');

        $this->command = $this->createProcessXmlFileCommand();
    }

    private function createProcessXmlFileCommand(): ProcessXmlFileCommand
    {
        $ftpService = new FtpService($_ENV['XML_FPT_DOMAIN'], $_ENV['XML_FTP_USERNAME'], $_ENV['XML_FTP_PASSWORD']);
        $googleSpreadsheetDataExportService = new GoogleSpreadsheetDataExportService($_ENV['GOOGLE_CREDENTIALS_PATH']);
        $simpleXmlFileDataParseService = new SimpleXmlFileDataParseService();
        $fileErrorLoggerService = new FileErrorLoggerService();

        $application = new Application();
        $command = new ProcessXmlFileCommand($ftpService, $googleSpreadsheetDataExportService, $simpleXmlFileDataParseService, $fileErrorLoggerService);
        $command->setApplication($application);
        return $command;
    }

    public function testLocal()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
        ]);

        $tester->assertCommandIsSuccessful();
    }

    public function testFtp()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'ftp',
        ]);

        $tester->assertCommandIsSuccessful();
    }

    public function testWrongFilename()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
            '--xmlFileName' => 'test1234.xml'
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function testWrongFilepath()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
            '--localFileFolder' => 'storagad'
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function testWrongParentElementName()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
            '--parentElementName' => 'itemzzz'
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function testNonExistingGoogleSheetId()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
            '--googleSheetsID' => 'notexisting'
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }

    public function testNonExistingCustomGoogleSheetTitle()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([
            'fileLocation' => 'local',
            '--googleSheetsTitle' => 'testTitle'
        ]);

        $this->assertEquals(1, $tester->getStatusCode());
    }
}
