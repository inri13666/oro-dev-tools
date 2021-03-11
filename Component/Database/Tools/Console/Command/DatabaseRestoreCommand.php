<?php

namespace Gorgo\Component\Database\Tools\Console\Command;

use Gorgo\Component\Database\Exception\EngineNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseRestoreCommand extends AbstractCommand
{
    const NAME = 'gorgo13:database:restore';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption('id', null, InputOption::VALUE_REQUIRED, '', null)
            ->addOption('remove', null, InputOption::VALUE_NONE, '', null);
    }

    /**
     * {@inheritdoc}
     *
     * @throws EngineNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getDatabaseConfiguration();
        $engine = $this->getEnginesRegistry()->findEngine($configuration);
        $sid = $input->getOption('id');
        $engine->restore($sid, $configuration);
        $output->writeln(sprintf('Restored dump with sid <info>%s</info>', $sid));
        if ($input->getOption('remove')) {
            $engine->drop($engine->getBackupDbName($sid, $configuration), $configuration);
            $output->writeln(sprintf('Backup with sid <info>%s</info> was dropped', $sid));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
