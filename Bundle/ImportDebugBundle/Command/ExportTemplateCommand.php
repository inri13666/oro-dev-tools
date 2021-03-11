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

class ExportTemplateCommand extends Command
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
        parent::__construct('gorgo:debug:export:template');
        $this->exportHandler = $exportHandler;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('processor-alias', null, InputOption::VALUE_REQUIRED);
        $this->addOption('file', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('options', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputFile = $input->getOption('file');
        if ($outputFile && !is_writable(dirname($outputFile))) {
            $output->writeln(sprintf('<error>ERROR:</error> Unable to write "%s"', $outputFile));

            return 1;
        }
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
        $exportFile = $result['file'] ?? null;
        if (!$exportFile || !$this->fileManager->isFileExist($exportFile)) {
            $output->writeln(sprintf('<error>ERROR</error> %s', 'file not generated'));
        }

        $content = $this->fileManager->getContent($exportFile);

        try {
            if ($outputFile) {
                file_put_contents($outputFile, $content);
                $output->writeln(sprintf(
                    '<info>INFO:</info> the export result written to "%s"',
                    realpath($outputFile)
                ));
            } else {
                echo $content;
            }
        } finally {
            $this->fileManager->deleteFile($exportFile);
        }

        return 0;
    }
}
