<?php

namespace Gorgo\Bundle\DatagridDebugBundle\Command;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DebugDatagridCommand extends Command
{
    const NAME = 'gorgo:datagrid:debug';

    /** @var ConfigurationProviderInterface */
    private $configurationProvider;

    /** @var Builder */
    private $datagridBuilder;

    public function __construct(ConfigurationProviderInterface $configurationProvider, Builder $datagridBuilder)
    {
        parent::__construct(self::NAME);
        $this->configurationProvider = $configurationProvider;
        $this->datagridBuilder = $datagridBuilder;
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gridName = $input->getArgument('datagrid');

        $table = new Table($output);
        $table->setHeaders([
            'Datagrid Name',
            'Type',
            'Parent',
        ]);
        $configuration = $this->configurationProvider->getConfiguration($gridName);

        $data = $configuration->toArray();
        //fix `extended_from`
        $extends = $data['extended_from'] ?? null;
        if ($extends) {
            $data['extends'] = end($extends);
        }
        unset($data['extended_from']);

        $table->addRow([
            $configuration->getName(),
            $configuration->getDatasourceType(),
            $data['extends'] ?? null,
        ]);

        $table->render();

        $definition['datagrids'][$gridName] = $data;
        $parameters = new ParameterBag($this->parseJsonOption($input->getOption('bind')));
        $additionalParameters = $this->parseJsonOption($input->getOption('additional'));
        $datagrid = $this->datagridBuilder->build($configuration, $parameters, $additionalParameters);
        $output->writeln('');
        $output->writeln('Datagrid configuration:');
        $output->writeln('');
        $output->writeln(Yaml::dump($datagrid->getConfig()->toArray(), 7));
        $output->writeln('');
        $dataSource = $datagrid->getAcceptedDatasource();
        if ($dataSource instanceof OrmDatasource) {
            $output->writeln('SQL:');
            $query = $dataSource->getQueryBuilder()->getQuery();
            $output->writeln($query->getSQL());
        } else {
            $output->writeln(sprintf('%s', get_class($dataSource)));
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
