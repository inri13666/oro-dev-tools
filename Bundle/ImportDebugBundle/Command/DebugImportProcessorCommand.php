<?php

declare(strict_types=1);

namespace Gorgo\Bundle\ImportDebugBundle\Command;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugImportProcessorCommand extends Command
{
    /**
     * @var ProcessorRegistry
     */
    private $processorRegistry;

    /**
     * @var ContextRegistry
     */
    private $contextRegistry;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ProcessorRegistry $processorRegistry,
        ContextRegistry $contextRegistry
    ) {
        parent::__construct('gorgo:debug:import:processor');
        $this->processorRegistry = $processorRegistry;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processor = $this->processorRegistry->getProcessor('import', 'wq_shipping.product_shipping_options');
        if ($processor instanceof StepExecutionAwareInterface && 0 == 1) {
            //todo: set step execution
            $processor->setStepExecution(new StepExecution());
        }

        $processor->setImportExportContext(new Context([]));

        $data = $processor->process([
            'MFG Item' => 'R0238',
            'Number of Units UOM' => 'EA',
            'Packaging' => 'Box',
            'Freight Class' => '050',
            'Package_1_Length' => '1',
            'Package_1_Height' => '2',
            'Package_1_Width' => '3',
            'Package_1_Weight' => '4',
        ]);

        var_dump($data);

        if ($processor instanceof ClosableInterface) {
            $processor->close();
        }
    }
}
