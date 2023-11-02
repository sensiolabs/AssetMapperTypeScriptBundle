<?php

namespace Sensiolabs\TypeScriptBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class TypeScriptBuilder
{
    private ?SymfonyStyle $output = null;
    private ?TypeScriptBinary $buildBinary = null;
    private ?WatchexecBinary $watchexecBinary = null;

    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $compiledFilesPaths,
        private readonly string $projectRootDir,
        private readonly ?string $binaryPath,
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
        $this->buildBinary = $this->buildBinary ?: $this->createBinary();
        $args = ['--out-dir', $this->compiledFilesPaths];
        $fs = new Filesystem();
        $relativePath = rtrim($fs->makePathRelative($path, $this->projectRootDir), '/');
        if (str_starts_with($relativePath, '..')) {
            throw new \Exception(sprintf('The TypeScript file "%s" is not in the project directory "%s".', $path, $this->projectRootDir));
        }
        $buildProcess = $this->buildBinary->createProcess(array_merge(['compile', $relativePath], $args));
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

        $this->watchexecBinary = $this->watchexecBinary ?: $this->createWatchexecBinary();
        $watchProcess = $this->watchexecBinary->createWatchProcess($relativePath);
        $watchProcess->setTimeout(null)->setIdleTimeout(null);
        $this->output?->note(sprintf('Watching for changes in %s...', $relativePath));
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$watchProcess->getCommandLine(),
            ]);
        }
        $watchProcess->start(function ($type, $buffer) {
            $path = trim($buffer);
            if ('/' === $path) {
                return;
            }
            $newProcess = $this->createBuildProcess($path);
            $newProcess->wait(function ($type, $buffer) {
                $this->output?->write($buffer);
            });
        });

        return $watchProcess;
    }

    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    private function createBinary(): TypeScriptBinary
    {
        return new TypeScriptBinary($this->projectRootDir.'/var', $this->binaryPath, $this->output);
    }

    public function createWatchexecBinary()
    {
        return new WatchexecBinary($this->projectRootDir.'/var', $this->binaryPath, $this->output);
    }
}
