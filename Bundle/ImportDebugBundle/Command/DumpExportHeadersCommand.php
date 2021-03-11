<?php

declare(strict_types=1);

namespace Gorgo\Bundle\ImportDebugBundle\Command;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DumpExportHeadersCommand extends Command
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

    /**
     * @var FileManager
     */
    private $fileManager;

    public function __construct(ExportHandler $exportHandler, FileManager $fileManager)
    {
        parent::__construct('gorgo:debug:export:dump-headers');
        $this->exportHandler = $exportHandler;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('processor-alias', null, InputOption::VALUE_REQUIRED);
        $this->addOption('options', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV;
        $processorAlias = $input->getOption('processor-alias');
        $processorType = ProcessorRegistry::TYPE_EXPORT_TEMPLATE;
        $result = $this->exportHandler->getExportResult(
            $jobName,
            $processorAlias,
            $processorType,
            'csv',
            null,
            $input->getOption('options')
        );

        if (!$result['success']) {
            if (count($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $output->writeln(sprintf('<error>ERROR:</error> %s', $error));
                }
            } else {
                $output->writeln(sprintf('<error>ERROR</error> %s', 'general error'));
            }

            return 1;
        }
        $file = $result['file'] ?? null;
        if (!$file || !$this->fileManager->isFileExist($file)) {
            $output->writeln(sprintf('<error>ERROR</error> %s', 'file not generated'));
        }
        try {
            $tmpFile = tempnam(sys_get_temp_dir(), 'export_');
            file_put_contents($tmpFile, $this->fileManager->getContent($file));

            $csv = fopen($tmpFile, 'r');
            $headers = fgetcsv($csv);
            echo Yaml::dump(['headers' => $headers]);
            fclose($csv);
            unlink($tmpFile);
        } finally {
            $this->fileManager->deleteFile($file);
        }

        return 0;
    }
}
