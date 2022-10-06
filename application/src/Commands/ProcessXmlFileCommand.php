<?php

namespace App\Commands;

use App\Services\FtpService;
use App\Services\GoogleSpreadSheetApiClientService;
use App\Services\XmlParseService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    const ARGUMENT_FILE_LOCATION = 'fileLocation';
    const ARGUMENT_XML_PARENT_ELEMENT_NAME = 'parentElementName';
    const ARGUMENT_XML_FILE_NAME = 'xmlFileName';
    const ARGUMENT_LOCAL_FILE_FOLDER = 'localFileFolder';

    const FILE_LOCATION_OPTION_FTP = 'ftp';
    const FILE_LOCATION_OPTION_STORAGE = 'storage';

    private Filesystem $fileSystem;

    public function __construct(private FtpService $ftpService, private GoogleSpreadSheetApiClientService $googleSpreadSheetApiClientService, private XmlParseService $xmlParseService, string $name = null)
    {
        $this->fileSystem = new Filesystem();
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->addArgument(self::ARGUMENT_FILE_LOCATION, InputArgument::OPTIONAL, '[ftp | storage] The location of the XML file we want to parse.', self::FILE_LOCATION_OPTION_STORAGE)
            ->addArgument(self::ARGUMENT_XML_FILE_NAME, InputArgument::OPTIONAL, 'The name of the xml file', self::DEFAULT_FTP_XML_FILE_NAME)
            ->addArgument(self::ARGUMENT_XML_PARENT_ELEMENT_NAME, InputArgument::OPTIONAL, 'XML parent element name of all items', self::DEFAULT_XML_PARENT_ELEMENT_NAME)
            ->addArgument(self::ARGUMENT_LOCAL_FILE_FOLDER, InputArgument::OPTIONAL, 'XML parent element name of all items', self::DEFAULT_LOCAL_XML_FOLDER);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            echo("starting XML parsing\n");
            match ($input->getArgument(self::ARGUMENT_FILE_LOCATION)) {
                self::FILE_LOCATION_OPTION_FTP => $fileName = $tmpFileName = $this->getTmpFileFromFtp($input->getArgument(self::ARGUMENT_XML_FILE_NAME)),
                self::FILE_LOCATION_OPTION_STORAGE => $fileName = $this->getStorageFileName($input->getArgument(self::ARGUMENT_LOCAL_FILE_FOLDER), $input->getArgument(self::ARGUMENT_XML_FILE_NAME))
            };

            echo(sprintf("parsing data from xml file: %s\n", $fileName));
            $values = $this->xmlParseService->getParsedDataFromXmlFile($fileName, $input->getArgument(self::ARGUMENT_XML_PARENT_ELEMENT_NAME));

            echo("inserting data into a new google spreadsheet\n");

            list ($spreadsheetId, $insertedCells) = $this->googleSpreadSheetApiClientService->insertDataIntoANewSpreadsheet($values);
            echo(sprintf("inserted %d cells into spreadsheet with ID: %s\n", $insertedCells, $spreadsheetId));

            return Command::SUCCESS;
        } catch (Exception $e) {
            //TODO LOG TO A FILE
            echo($e->getMessage());
        } finally {
            if (isset($tmpFileName) && file_exists($tmpFileName)) {
                unlink($tmpFileName);
            }
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
}