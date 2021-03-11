<?php

namespace Gorgo\Bundle\DatagridDebugBundle\Command;

use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ProfileDatagridCommand extends Command
{
    const NAME = 'gorgo:datagrid:profile';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Manager */
    private $datagridManager;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        Manager $datagridManager,
        TranslatorInterface $translator
    ) {
        parent::__construct(self::NAME);
        $this->eventDispatcher = $eventDispatcher;
        $this->datagridManager = $datagridManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('datagrid', InputArgument::REQUIRED)
            ->addOption('bind', null, InputOption::VALUE_OPTIONAL, 'JSON string or path to JSON file', '{}')
            ->addOption('additional', null, InputOption::VALUE_OPTIONAL, 'JSON string or path to JSON file', '{}');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$input->getOption('current-user')) {
            $output->writeln(sprintf('Option "%s" required', 'current-user'));

            return 1;
        }

        if (!$input->getOption('current-organization')) {
            $output->writeln(sprintf('Option "%s" required', 'current-organization'));

            return 1;
        }

        $queryData = [];
        $this->eventDispatcher->addListener(OrmResultAfter::NAME, function (OrmResultAfter $event) use (&$queryData) {
            $datagrid = $event->getDatagrid();
            if ($datagrid->getAcceptedDatasource() instanceof OrmDatasource) {
                $query = $event->getQuery();
                $queryData[$datagrid->getName()][] = [
                    'sql' => $query->getSQL(),
                    'parameters' => $query->getParameters()->toArray(),
                ];
            }
        });

        $datagridName = $input->getArgument('datagrid');
        $parameters = $this->parseJsonOption($input->getOption('bind'));
        $additionalParameters = $this->parseJsonOption($input->getOption('additional'));

        $datagrid = $this->datagridManager->getDatagrid($datagridName, $parameters, $additionalParameters);
        $config = $datagrid->getConfig();
        $columns = $config->offsetGet('columns');
        $headers = [];
        foreach ($columns as $column => $options) {
            $headers[$column] = $options['translatable']
                ? $this->translator->trans($options['label'])
                : $options['label'];
        }
        $table = new Table($output);
        $table->setHeaders($headers);
        $data = $datagrid->getData()->toArray();
        foreach ($data['data'] as $item) {
            $row = [];
            foreach ($headers as $column => $title) {
                if (is_array($item[$column])) {
                    $first = reset($item[$column]);
                    if (is_array($first)) {
                        $row[$column] = 'ArrayData';//json_encode($item[$column]);
                    } else {
                        $row[$column] = implode(',', $item[$column]);
                    }
                    continue;
                }
                $row[$column] = $item[$column];
            }
            $table->addRow($row);
        }
        $table->render();

        if (!empty($queryData[$datagridName])) {
            $output->writeln('');
            $output->writeln('SQL Query:');
            $output->writeln($queryData[$datagridName][0]['sql']);
            $output->writeln('');
            $parameters = $queryData[$datagridName][0]['parameters'];
            if (count($parameters)) {
                $output->writeln('SQL Parameters:');
                $table = new Table($output);
                $table->setHeaders([
                    'name',
                    'value',
                    'type',
                ]);
                /** @var Parameter $parameter */
                foreach ($parameters as $parameter) {
                    $value = $parameter->getValue();
                    $table->addRow([
                        $parameter->getName(),
                        is_array($value) ? implode(',', $value) : $value,
                        $parameter->getType(),
                    ]);
                }
                $table->render();
            }
        }
    }

    private function parseJsonOption($data): ?array
    {
        if (is_file($data)) {
            $data = file_get_contents($data);
        }

        $data = json_decode($data, true);

        if (json_last_error()) {
            return null;
        }

        return $data;
    }
}
