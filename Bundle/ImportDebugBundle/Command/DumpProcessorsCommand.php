<?php

declare(strict_types=1);

namespace Gorgo\Bundle\ImportDebugBundle\Command;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpProcessorsCommand extends Command
{
    /**
     * @var ProcessorRegistry
     */
    private $processorRegistry;

    public function __construct(ProcessorRegistry $processorRegistry)
    {
        parent::__construct('gorgo:debug:import:dump-processors');
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('type', null, InputOption::VALUE_OPTIONAL, sprintf('available options : [%s]', implode(',', [
            ProcessorRegistry::TYPE_EXPORT,
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            ProcessorRegistry::TYPE_IMPORT,
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
        ])), ProcessorRegistry::TYPE_EXPORT);
        $this->addOption('entity-class', null, InputOption::VALUE_OPTIONAL, '', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $entityClass = $input->getOption('entity-class');
        if ($entityClass) {
            $processors = $this->processorRegistry->getProcessorsByEntity($type, $entityClass);
        } else {
            $processors = $this->processorRegistry->getProcessorsByType($type);
        }

        $table = new Table($output);
        $table->setHeaders([
            'alias',
            'class',
        ]);
        foreach ($processors as $alias => $processor) {
            $table->addRow([
                $alias,
                get_class($processor),
            ]);
        }

        $table->render();

        return 0;
    }
}
