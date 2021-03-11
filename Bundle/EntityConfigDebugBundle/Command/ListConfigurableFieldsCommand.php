<?php

declare(strict_types=1);

namespace Gorgo\Bundle\EntityConfigDebugBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListConfigurableFieldsCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        parent::__construct('gorgo:debug:entity-config:configurable-fields');
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('entity', null, InputOption::VALUE_REQUIRED)
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_NONE,
                'Show configuration values from a cache. By default values are loaded from a database'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityClass = $input->getOption('entity');
        /** @var Connection $connection */
        $connection = $this->registry->getConnection();

        $rows = $connection->fetchAll(
            'SELECT fc.field_name, fc.type, fc.mode, fc.data FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ?'
            . ' ORDER BY fc.field_name',
            [$entityClass],
            [\PDO::PARAM_STR]
        );
        $rows = array_map(function (array $row) {
            $row['data'] = $row['data'] ? unserialize(base64_decode($row['data'])) : [];

            if (isset($row['data']['importexport'])) {
                $row['data'] = json_encode($row['data']['importexport']);
            } else {
                $row['data'] = json_encode([]);
            }

            return $row;
        }, $rows);
        $table = new Table($output);
        $table->setHeaders(['field name', 'type', 'mode', 'import/export']);
        $table->setRows($rows);
        $table->render();

        return 0;
    }
}
