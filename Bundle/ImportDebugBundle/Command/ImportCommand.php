<?php

declare(strict_types=1);

namespace Gorgo\Bundle\ImportDebugBundle\Command;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ImportCommand extends Command
{
    /**
     * @var AbstractImportHandler|string|null
     */
    private $importHandler;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(AbstractImportHandler $importHandler, FileManager $fileManager)
    {
        parent::__construct('gorgo:debug:import');
        $this->importHandler = $importHandler;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            'processor-alias',
            null,
            InputOption::VALUE_REQUIRED,
            '',
            'oro_product_product.add_or_replace'
        );
        $this->addOption('file', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('validate', null, InputOption::VALUE_NONE);
        $this->addOption('options', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getOption('file');
        $importFileName = FileManager::generateUniqueFileName(pathinfo($fileName, PATHINFO_EXTENSION));
        $this->fileManager->saveFileToStorage(new \SplFileInfo($fileName), $importFileName);

        if ($input->getOption('validate')) {
            $jobName = JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV;
            $process = ProcessorRegistry::TYPE_IMPORT_VALIDATION;
        } else {
            $jobName = JobExecutor::JOB_IMPORT_FROM_CSV;
            $process = ProcessorRegistry::TYPE_IMPORT;
        }

        $processorAlias = $input->getOption('processor-alias');


        $filePath = $this->fileManager->writeToTmpLocalStorage($importFileName);
        try {
            $this->importHandler->setImportingFileName($filePath);
            $result = $this->importHandler->handle(
                $process,
                $jobName,
                $processorAlias,
                $input->getOption('options') ?? []
            );
        } finally {
            $this->fileManager->deleteFile($importFileName);
            unlink($filePath);
        }

        $table = new Table($output);
        $table->addRows([
            ['errors', $result['counts']['errors'] ?? 0],
            ['process', $result['counts']['process'] ?? 0],
            ['read', $result['counts']['read'] ?? 0],
            ['add', $result['counts']['add'] ?? 0],
            ['replace', $result['counts']['replace'] ?? 0],
            ['update', $result['counts']['update'] ?? 0],
            ['error_entries', $result['counts']['error_entries'] ?? 0],
        ]);
        $table->render();

        if (isset($result['errors']) && count($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $output->writeln(sprintf('<error>ERROR:</error> %s', $error));
            }
        }

        echo Yaml::dump($result);
    }
}
