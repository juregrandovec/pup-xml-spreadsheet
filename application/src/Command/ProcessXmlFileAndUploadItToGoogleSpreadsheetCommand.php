<?php

namespace App\Command;

use App\Service\FtpService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'process')]
class ProcessXmlFileAndUploadItToGoogleSpreadsheetCommand extends Command
{
    public function __construct(private FtpService $ftpService, string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ftpService->getFileFromFtp("test.xml", "coffee_feed.xml");


        return Command::SUCCESS;
    }
}