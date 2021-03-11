<?php

namespace Gorgo\Component\Database\Tools\Console\Command;

use Gorgo\Component\Database\Model\DatabaseConfigurationInterface;
use Gorgo\Component\Database\Service\DatabaseEngineRegistry;
use Gorgo\Component\Database\Tools\Console\Helper\DatabaseConfigurationHelper;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    /** @var DatabaseConfigurationInterface */
    private $databaseConfiguration;

    /** @var DatabaseEngineRegistry */
    private $enginesRegistry;

    /**
     * @return DatabaseConfigurationInterface
     */
    public function getDatabaseConfiguration()
    {
        if (!$this->databaseConfiguration) {
            /** @var DatabaseConfigurationHelper $helper */
            $helper = $this->getHelper(DatabaseConfigurationHelper::HELPER_NAME);
            $this->databaseConfiguration = $helper->getConfiguration();
        }

        return $this->databaseConfiguration;
    }

    /**
     * @param DatabaseConfigurationInterface $databaseConfiguration
     *
     * @return $this
     */
    public function setDatabaseConfiguration($databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;

        return $this;
    }

    /**
     * @return DatabaseEngineRegistry
     */
    public function getEnginesRegistry()
    {
        if (!$this->enginesRegistry) {
            /** @var DatabaseConfigurationHelper $helper */
            $helper = $this->getHelper(DatabaseConfigurationHelper::HELPER_NAME);
            $this->enginesRegistry = $helper->getEnginesRegistry();
        }

        return $this->enginesRegistry;
    }

    /**
     * @param DatabaseEngineRegistry $enginesRegistry
     *
     * @return $this
     */
    public function setEnginesRegistry($enginesRegistry)
    {
        $this->enginesRegistry = $enginesRegistry;

        return $this;
    }
}
