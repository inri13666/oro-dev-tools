<?php

namespace Gorgo\BehatExtension\CacheBehatExtension\Isolator\FileCache;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\AbstractFileCacheOsRelatedIsolator;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\AbstractOsRelatedIsolator;
use Symfony\Component\Process\Process;

/**
 * Manages actualization of cache during tests.
 */
class WindowsFileCacheIsolator extends AbstractFileCacheOsRelatedIsolator
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Cache';
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::WINDOWS_OS,
        ];
    }

    protected function replaceCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $cacheTempDirPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheTempDir . '\\' . $directory);

            if (!is_dir($cacheTempDirPath)) {
                continue;
            }

            $commands[] = sprintf(
                'move %s %s',
                $cacheTempDirPath,
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDir . '\\' . $directory)
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }

    protected function startCopyDumpToTempDir()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDumpDir . '\\' . $directory),
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheTempDir . '\\' . $directory)
            );
        }

        $this->copyDumpToTempDirProcess = new Process(implode(' & ', $commands));

        $this->copyDumpToTempDirProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }

    protected function dumpCache()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDir . '\\' . $directory);

            if (!is_dir($cacheDirPath)) {
                continue;
            }

            $commands[] = sprintf(
                'xcopy %s %s /E /R /H /I /K /Y',
                $cacheDirPath,
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDumpDir . '\\' . $directory)
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }

    protected function removeDumpCacheDir()
    {
        $this->runProcess(
            sprintf('rd /s /q %s', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDumpDir))
        );
    }

    protected function removeTempCacheDir()
    {
        $this->runProcess(
            sprintf('rd /s /q %s', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheTempDir))
        );
    }

    protected function removeCacheDirs()
    {
        $commands = [];

        foreach ($this->cacheDirectories as $directory) {
            $cacheDirPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDir . '\\' . $directory);

            if (!is_dir($cacheDirPath)) {
                continue;
            }

            $commands[] = sprintf(
                'rd /s /q %s',
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->cacheDir . '\\' . $directory)
            );
        }

        $this->runProcess(implode(' & ', $commands));
    }
}
