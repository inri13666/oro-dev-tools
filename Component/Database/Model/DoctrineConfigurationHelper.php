<?php

namespace Gorgo\Component\Database\Model;

use Doctrine\DBAL\Connection;

class DoctrineConfigurationHelper
{
    /**
     * @param Connection $connection
     *
     * @return DoctrineConfigurationHelper
     */
    public static function fromDbalConnection(Connection $connection)
    {
        $conf = new DatabaseConfigurationModel();
        $conf->setDriver($connection->getDriver()->getName())
            ->setHost($connection->getHost())
            ->setPassword($connection->getPassword())
            ->setDbName($connection->getDatabase())
            ->setUser($connection->getUsername())
            ->setPort($connection->getPort());

        return $conf;
    }
}
