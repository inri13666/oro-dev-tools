<?php

namespace Gorgo\Component\Database\Tools\Console\Helper;

use Gorgo\Component\Database\Engine\DatabaseEngineInterface;
use Gorgo\Component\Database\Engine\MysqlDatabaseEngine;
use Gorgo\Component\Database\Model\DatabaseConfigurationInterface;
use Gorgo\Component\Database\Service\DatabaseEngineRegistry;
use Symfony\Component\Console\Helper\Helper;

class DatabaseConfigurationHelper extends Helper
{
    const HELPER_NAME = 'gorgo13_database_connection';

    /** @var DatabaseConfigurationInterface */
    protected $configuration;

    /** @var DatabaseEngineRegistry */
    protected $enginesRegistry;

    /**
     * @param DatabaseConfigurationInterface $configuration
     * @param DatabaseEngineInterface[] $engines
     */
    public function __construct(DatabaseConfigurationInterface $configuration, array $engines = [])
    {
        $this->configuration = $configuration;
        $this->enginesRegistry = new DatabaseEngineRegistry();
        if (empty($engines)) {
            $this->enginesRegistry->addEngine(new MysqlDatabaseEngine());
        } else {
            foreach ($engines as $engine) {
                $this->enginesRegistry->addEngine($engine);
            }
        }
    }

    /**
     * @return DatabaseConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return DatabaseEngineRegistry
     */
    public function getEnginesRegistry()
    {
        return $this->enginesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::HELPER_NAME;
    }
}
