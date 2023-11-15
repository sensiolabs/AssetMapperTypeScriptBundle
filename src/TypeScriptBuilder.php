<?php

namespace Sensiolabs\TypeScriptBundle;

use Sensiolabs\TypeScriptBundle\Tools\TypeScriptBinary;
use Sensiolabs\TypeScriptBundle\Tools\TypescriptBinaryFactory;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinary;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinaryFactory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class TypeScriptBuilder
{
    private ?SymfonyStyle $output = null;
    private ?TypeScriptBinary $buildBinary = null;
    private ?WatcherBinary $watchexecBinary = null;

    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $compiledFilesPaths,
        private readonly string $projectRootDir,
        private readonly string $binaryDownloadDir,
        private readonly ?string $buildBinaryPath,
        private readonly ?string $watchexecBinaryPath,
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
            throw new \Exception(sprintf('The TypeScript file "%s" is not in the project directory "%s".', $path, $this->projectRootDir));
        }
        $buildProcess = $this->getBuildBinary()->createProcess(array_merge(['compile', $relativePath], $args));
        $buildProcess->setWorkingDirectory($this->projectRootDir);

        $this->output?->note(sprintf('Executing SWC compile on %s.', $relativePath));
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$buildProcess->getCommandLine(),
            ]);
        }
        $buildProcess->start();

        if (false === $watch) {
            return $buildProcess;
        }

        return $this->getWatchexecBinary()->startWatch($relativePath, fn ($path, $operation) => $this->createBuildProcess($path), ['ts']);
        //
        //        $watchProcess = $this->getWatchexecBinary()->createProcess($relativePath);
        //        $watchProcess->setTimeout(null)->setIdleTimeout(null);
        //        $this->output?->note(sprintf('Watching for changes in %s...', $relativePath));
        //        if ($this->output?->isVerbose()) {
        //            $this->output->writeln([
        //                '  Command:',
        //                '    '.$watchProcess->getCommandLine(),
        //            ]);
        //        }
        //        $watchProcess->start(function ($type, $buffer) {
        //            if ('err' === $type) {
        //                throw new \RuntimeException($buffer);
        //            }
        //            $path = trim($buffer);
        //            if ('/' === $path) {
        //                return;
        //            }
        //            $newProcess = $this->createBuildProcess($path);
        //            $newProcess->wait(function ($type, $buffer) {
        //                $this->output?->write($buffer);
        //            });
        //        });
        //
        //        return $watchProcess;
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
        $typescriptBinaryFactory = new TypescriptBinaryFactory($this->binaryDownloadDir);
        $typescriptBinaryFactory->setOutput($this->output);

        return $this->buildBinary = $this->buildBinaryPath ?
            $typescriptBinaryFactory->getBinaryFromPath($this->buildBinaryPath) :
            $typescriptBinaryFactory->getBinaryFromServerSpecs(\PHP_OS, php_uname('m'), php_uname('r'));
    }

    private function getWatchexecBinary(): WatcherBinary
    {
        if ($this->watchexecBinary) {
            return $this->watchexecBinary;
        }
        $watchexecBinaryFactory = new WatcherBinaryFactory();

        return $this->watchexecBinary = $watchexecBinaryFactory->getBinaryFromServerSpecs(\PHP_OS, php_uname('m'), php_uname('r'));
    }
}
