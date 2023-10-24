<?php

namespace Sensiolabs\TypeScriptBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class TypeScriptBuilder
{
    private ?SymfonyStyle $output = null;

    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $compiledFilesPaths,
        private readonly string $projectRootDir,
        private readonly ?string $binaryPath)
    {
    }

    public function runBuild(): \Generator
    {
        $binary = $this->createBinary();

        $args = ['--out-dir', $this->compiledFilesPaths];

        $fs = new Filesystem();
        foreach ($this->typeScriptFilesPaths as $typeScriptFilePath) {
            $relativePath = $fs->makePathRelative($typeScriptFilePath, $this->projectRootDir);
            if (str_starts_with($relativePath, '..')) {
                throw new \Exception(sprintf('The TypeScript file "%s" is not in the project directory "%s".', $typeScriptFilePath, $this->projectRootDir));
            }
            $process = $binary->createProcess(array_merge(['compile', $relativePath], $args));
            $process->setWorkingDirectory($this->projectRootDir);

            $this->output?->note(sprintf('Executing SWC compile on %s.', $typeScriptFilePath));
            if ($this->output?->isVerbose()) {
                $this->output->writeln([
                    '  Command:',
                    '    '.$process->getCommandLine(),
                ]);
            }

            $process->start();

            yield $process;
        }
    }

    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    private function createBinary(): TypeScriptBinary
    {
        return new TypeScriptBinary($this->projectRootDir.'/var', $this->binaryPath, $this->output);
    }
}
