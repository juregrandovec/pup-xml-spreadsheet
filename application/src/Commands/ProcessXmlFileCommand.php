<?php

namespace App\Commands;

use App\Services\FtpService;
use App\Services\GoogleSpreadSheetApiClientService;
use App\Services\LoggerService;
use App\Services\XmlParseService;
use Exception;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'process-xml',
    description: 'Download XML file from FTP or use XML file from storage, parse it and push it to google spreadsheet',
    aliases: ['xml'],
    hidden: false
)]
class ProcessXmlFileCommand extends Command
{
    const TMP_FILE_PREFIX = 'tmp_';
    const TMP_FOLDER_PATH = './storage/tmp';

    const DEFAULT_FTP_XML_FILE_NAME = 'coffee_feed.xml';
    const DEFAULT_LOCAL_XML_FOLDER = './storage';
    const DEFAULT_XML_PARENT_ELEMENT_NAME = 'item';
    const DEFAULT_GOOGLE_SHEETS_TITLE = 'sheet1';

    const ARGUMENT_FILE_LOCATION = 'fileLocation';
    const ARGUMENT_XML_PARENT_ELEMENT_NAME = 'parentElementName';
    const ARGUMENT_XML_FILE_NAME = 'xmlFileName';
    const ARGUMENT_LOCAL_FILE_FOLDER = 'localFileFolder';
    const ARGUMENT_SHEET_ID = 'googleSheetsID';
    const ARGUMENT_SHEET_TITLE = 'googleSheetsTitle';

    const FILE_LOCATION_OPTION_FTP = 'ftp';
    const FILE_LOCATION_OPTION_LOCAL = 'local';

    const FILE_LOCATION_OPTIONS = [
        self::FILE_LOCATION_OPTION_FTP,
        self::FILE_LOCATION_OPTION_LOCAL
    ];

    private Filesystem $fileSystem;
    private string $xmlFileName;
    private string $localFileFolder;
    private string $xmlParentElementName;
    private ?string $spreadsheetId;
    private string $spreadsheetTitle;
    private string $tmpFileName;

    public function __construct(
        private FtpService                        $ftpService,
        private GoogleSpreadSheetApiClientService $googleSpreadSheetApiClientService,
        private XmlParseService                   $xmlParseService,
        private LoggerService                     $loggerService,
        string                                    $name = null)
    {
        $this->fileSystem = new Filesystem();
        parent::__construct($name);
    }

    /**
     * @param ConsoleLogger $consoleLogger
     * @param Exception $e
     * @return void
     */
    private function logError(ConsoleLogger $consoleLogger, Exception $e): void
    {
        $consoleLogger->error(sprintf("Error %s", $e->getMessage()), [$e]);
        $this->loggerService->error($e->getMessage());
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws Exception
     */
    private function getFilename(InputInterface $input): string
    {
        match ($input->getArgument(self::ARGUMENT_FILE_LOCATION)) {
            self::FILE_LOCATION_OPTION_FTP => $fileName = $this->tmpFileName = $this->getTmpFileFromFtp($this->xmlFileName),
            self::FILE_LOCATION_OPTION_LOCAL => $fileName = $this->getStorageFileName($this->localFileFolder, $this->xmlFileName),
            default => throw new Exception("File location argument not valid ")
        };
        return $fileName;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    private function collectInputs(InputInterface $input): void
    {
        $this->xmlFileName = $input->getOption(self::ARGUMENT_XML_FILE_NAME);
        $this->localFileFolder = $input->getOption(self::ARGUMENT_LOCAL_FILE_FOLDER);
        $this->xmlParentElementName = $input->getOption(self::ARGUMENT_XML_PARENT_ELEMENT_NAME);
        $this->spreadsheetId = $input->getOption(self::ARGUMENT_SHEET_ID);
        $this->spreadsheetTitle = $input->getOption(self::ARGUMENT_SHEET_TITLE);
    }

    protected function configure()
    {
        $this->addArgument(self::ARGUMENT_FILE_LOCATION, InputArgument::OPTIONAL, sprintf('[%s] The location of the XML file we want to parse.', implode(" | ", self::FILE_LOCATION_OPTIONS)), self::FILE_LOCATION_OPTION_LOCAL)
            ->addOption(self::ARGUMENT_XML_FILE_NAME, 'x', InputArgument::OPTIONAL, 'The name of the xml file', self::DEFAULT_FTP_XML_FILE_NAME)
            ->addOption(self::ARGUMENT_XML_PARENT_ELEMENT_NAME, 'e', InputArgument::OPTIONAL, 'XML element name containing items', self::DEFAULT_XML_PARENT_ELEMENT_NAME)
            ->addOption(self::ARGUMENT_LOCAL_FILE_FOLDER, 'f', InputArgument::OPTIONAL, 'Path to folder containing local xml file', self::DEFAULT_LOCAL_XML_FOLDER)
            ->addOption(self::ARGUMENT_SHEET_ID, 'i', InputArgument::OPTIONAL, 'Set GoogleSheetsID', null)
            ->addOption(self::ARGUMENT_SHEET_TITLE, 't', InputArgument::OPTIONAL, 'Set GoogleSheet Title', self::DEFAULT_GOOGLE_SHEETS_TITLE);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $consoleLogger = new ConsoleLogger($output);

        try {
            $this->collectInputs($input);

            $consoleLogger->info("starting XML parsing");
            $fileName = $this->getFilename($input);

            $consoleLogger->info(sprintf("parsing data from xml file: %s", $fileName));
            $values = $this->xmlParseService->getParsedDataFromXmlFile($fileName, $this->xmlParentElementName);

            $consoleLogger->info("inserting data into a new google spreadsheet");
            list ($spreadsheetId, $insertedCells) = $this->googleSpreadSheetApiClientService->insertDataIntoASpreadsheet($values, $this->spreadsheetTitle, $this->spreadsheetId);

            $consoleLogger->info(sprintf("inserted %d cells into spreadsheet with ID: %s", $insertedCells, $spreadsheetId));

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->logError($consoleLogger, $e);
        } finally {
            $this->unlinkTmpFile();
        }

        return Command::FAILURE;
    }

    /**
     * @return string
     */
    private function getTmpXmlFileName(): string
    {
        return $this->fileSystem->tempnam(self::TMP_FOLDER_PATH, self::TMP_FILE_PREFIX, '.xml');
    }

    /**
     * @throws Exception
     */
    private function getTmpFileFromFtp(string $fileName): string
    {
        $tmpXmlFileName = $this->getTmpXmlFileName();
        return $this->ftpService->getFileFromFtp($tmpXmlFileName, $fileName);
    }

    /**
     * @param string $folder
     * @param string $fileName
     * @return mixed
     * @throws Exception
     */
    private function getStorageFileName(string $folder, string $fileName): string
    {
        $filePath = sprintf("%s/%s", $folder, $fileName);
        if (!$this->fileSystem->exists($filePath)) {
            throw new Exception("Xml file not found!\n");
        }

        return $filePath;
    }

    private function unlinkTmpFile()
    {
        if (isset($this->tmpFileName) && file_exists($this->tmpFileName)) {
            unlink($this->tmpFileName);
        }
    }
}