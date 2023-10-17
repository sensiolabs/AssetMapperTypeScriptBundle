<?php

namespace Sensiolabs\TypescriptBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfonycasts\SassBundle\SassBinary;

class TypeScriptBuilder
{
    private ?SymfonyStyle $output = null;

    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $compiledFilesPaths,
        private readonly string $projectRootDir,
        private readonly ?string $binaryPath,
        private readonly bool $embedSourcemap,
    )
    {
    }

    public function runBuild(bool $watch): \Generator
    {
        $binary = $this->createBinary();

        $args = ['--out-dir', $this->compiledFilesPaths];
        if ($watch) {
            // TODO: dl chokidar ?
            $args[] = '--watch';
        }

        if ($this->embedSourcemap) {
            $args = array_merge($args, ['--source-maps', 'true']);
        }
        $fs = new Filesystem();
        foreach ($this->typeScriptFilesPaths as $typeScriptFilePath) {
            $relativePath = $fs->makePathRelative($typeScriptFilePath, $this->projectRootDir);
            if (str_starts_with($relativePath, '..')) {
                throw new \Exception(sprintf('The TypeScript file "%s" is not in the project directory "%s".', $typeScriptFilePath, $this->projectRootDir));
            }
            $process = $binary->createProcess(array_merge(['compile', $relativePath], $args));
            $process->setWorkingDirectory($this->projectRootDir); // TODO: handle multiple directories

            $this->output?->note(sprintf('Executing SWC compile on %s (pass -v to see more details).', $typeScriptFilePath));
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

    /**
     * @return array<string>
     */
    public function getTypeScriptTargets(): array
    {
        $files = [];
        $dirs = [];
        foreach ($this->typeScriptFilesPaths as $typeScriptFilePath) {
            if (is_dir($typeScriptFilePath)) {
                $dirs[] = $typeScriptFilePath;
                continue;
            }
            if (is_file($typeScriptFilePath)) {
                $files[] = $typeScriptFilePath;
                continue;
            }
            throw new \Exception(sprintf('Could not find TypeScript file or directory : "%s"', $typeScriptFilePath));
        }

        if( \count($dirs) && \count($files) ) {
            throw new \Exception('Cannot compile TypeScript files and directories at the same time.');
        }
        if(\count($dirs) > 1) {
            throw new \Exception('Cannot compile multiple TypeScript directories at the same time.');
        }

        return $files ?: $dirs;
    }

    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * @internal
     */
    public static function guessJsNameFromTypeScriptFile(string $sassFile, string $outputDirectory): string
    {
        $fileName = basename($sassFile, '.scss');

        return $outputDirectory.'/'.$fileName.'.output.css';
    }

    private function createBinary(): TypeScriptBinary
    {
        return new TypeScriptBinary($this->projectRootDir.'/var', $this->binaryPath, $this->output);
    }
}
