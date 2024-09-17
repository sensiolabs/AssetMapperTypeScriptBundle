<?php

namespace Sensiolabs\TypeScriptBundle;

use Sensiolabs\TypeScriptBundle\Tools\TypeScriptBinary;
use Sensiolabs\TypeScriptBundle\Tools\TypeScriptBinaryFactory;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinary;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinaryFactory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class TypeScriptBuilder
{
    private SymfonyStyle $output;
    private ?TypeScriptBinary $buildBinary = null;
    private ?WatcherBinary $watcherBinary = null;

    /**
     * @param list<string> $typeScriptFilesPaths
     */
    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $compiledFilesPaths,
        private readonly string $projectRootDir,
        private readonly string $binaryDownloadDir,
        private readonly ?string $buildBinaryPath,
        private readonly ?string $configFile,
        private readonly string $swcVersion,
    ) {
    }

    public function createAllBuildProcess(bool $watch = false): \Generator
    {
        foreach ($this->typeScriptFilesPaths as $typeScriptFilePath) {
            yield $this->createBuildProcess($typeScriptFilePath, $watch);
        }
    }

    private function createBuildProcess(string $path, bool $watch = false): Process
    {
        $args = ['--out-dir', $this->compiledFilesPaths];
        $fs = new Filesystem();
        $relativePath = rtrim($fs->makePathRelative($path, $this->projectRootDir), '/');
        if (str_starts_with($relativePath, '..')) {
            throw new \Exception(\sprintf('The TypeScript file "%s" is not in the project directory "%s".', $path, $this->projectRootDir));
        }
        if ($this->configFile && file_exists($this->configFile)) {
            $args = array_merge($args, ['--config-file', trim($fs->makePathRelative($this->configFile, $this->projectRootDir), '/')]);
        }
        $buildProcess = $this->getBuildBinary()->createProcess(array_merge(['compile', $relativePath], $args));
        $buildProcess->setWorkingDirectory($this->projectRootDir);

        $this->output->note(\sprintf('Executing SWC compile on %s.', $relativePath));
        if ($this->output->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$buildProcess->getCommandLine(),
            ]);
        }
        $buildProcess->start();

        if (false === $watch) {
            return $buildProcess;
        }

        return $this->getWatcherBinary()->startWatch($relativePath, fn ($path, $operation) => $this->createBuildProcess($path), ['ts']);
    }

    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    private function getBuildBinary(): TypeScriptBinary
    {
        if ($this->buildBinary) {
            return $this->buildBinary;
        }
        $typescriptBinaryFactory = new TypeScriptBinaryFactory($this->binaryDownloadDir);
        $typescriptBinaryFactory->setOutput($this->output);

        return $this->buildBinary = $this->buildBinaryPath ?
            $typescriptBinaryFactory->getBinaryFromPath($this->buildBinaryPath) :
            $typescriptBinaryFactory->getBinaryFromServerSpecs(\PHP_OS, php_uname('m'), file_exists('/etc/alpine-release') ? 'musl' : 'gnu');
    }

    private function getWatcherBinary(): WatcherBinary
    {
        if ($this->watcherBinary) {
            return $this->watcherBinary;
        }
        $watcherBinaryFactory = new WatcherBinaryFactory();

        return $this->watcherBinary = $watcherBinaryFactory->getBinaryFromServerSpecs(\PHP_OS);
    }
}
