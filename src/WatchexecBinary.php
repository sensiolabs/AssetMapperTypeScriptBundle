<?php

namespace Sensiolabs\TypeScriptBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WatchexecBinary
{
    private const VERSION = '1.20.5';
    private const WATCHEXEC_RELEASE_URL_PATTERN = 'https://github.com/watchexec/watchexec/releases/download/cli-v%s/%s';

    public function __construct(
        private readonly string $binaryDownloadDir,
        private readonly ?string $binaryPath,
        private ?SymfonyStyle $output = null,
        private ?HttpClientInterface $httpClient = null,
        private ?string $binaryName = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * @param array<string> $args
     */
    public function createWatchProcess(string $watchPath): Process
    {
        $args = ['--exts', 'ts', '-w', $watchPath, 'echo "$WATCHEXEC_COMMON_PATH/$WATCHEXEC_WRITTEN_PATH"'];
        if (null === $this->binaryPath) {
            $binary = $this->getDefaultBinaryPath();
            if (!is_file($binary)) {
                $this->downloadBinary();
            }
        } else {
            $binary = $this->binaryPath;
        }

        array_unshift($args, $binary);

        return new Process($args);
    }

    public function downloadBinary(): void
    {
        $targetPath = $this->binaryDownloadDir.'/'.$this->getBinaryArchiveName();
        if (file_exists($targetPath)) {
            return;
        }

        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }

        $url = sprintf(self::WATCHEXEC_RELEASE_URL_PATTERN, self::VERSION, $this->getBinaryArchiveName());
        $this->output?->note(sprintf('Downloading Watchexec binary to %s...', $targetPath));

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output?->createProgressBar($dlSize);
                }

                $progressBar?->setProgress($dlNow);
            },
        ]);
        $fileHandler = fopen($targetPath, 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        fclose($fileHandler);
        $progressBar?->finish();
        $this->output?->writeln('');

        $extractProcess = new Process(['tar', '-xf', $targetPath]);
        $extractProcess->setWorkingDirectory($this->binaryDownloadDir);
        $extractProcess->run();
        unlink($targetPath);

        chmod($this->getDefaultBinaryPath(), 7770);
    }

    private function getBinaryArchiveName(): string
    {
        if (null !== $this->binaryName) {
            return $this->binaryName;
        }
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));
        $kernel = strtolower(php_uname('r'));

        if (str_contains($os, 'darwin')) {
            if ('aarch64' === $machine) {
                $platform = 'aarch64-apple-darwin.tar.xz';
            } elseif ('x86_64' === $machine){
                $platform = 'x86_64-apple-darwin.tar.xz';
            }
        }
        if (str_contains($os, 'linux')) {
            $kernelVersion = str_contains($kernel, 'musl') ? 'musl' : 'gnu';
            if ('arm64' === $machine || 'aarch64' === $machine) {
                $platform = 'aarch64-unknown-linux-'.$kernelVersion.'.tar.xz';
            } elseif ('x86_64' === $machine) {
                $platform = 'x86_64-unknown-linux-'.$kernelVersion.'.tar.xz';
            }
        }
        if (str_contains($os, 'win')) {
            if ('x86_64' === $machine) {
                $platform = 'x86_64-pc-windows-msvc.zip';
            }
        }

        if (!isset($platform)) {
            throw new \Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
        }

        $this->binaryName = sprintf('watchexec-%s-%s', self::VERSION, $platform);

        return $this->binaryName;
    }

    private function getDefaultBinaryPath(): string
    {
        $archiveName = $this->getBinaryArchiveName();
        $extension = str_ends_with($archiveName, '.tar.xz') ? '.tar.xz' : '.zip';
        return $this->binaryDownloadDir.'/'.basename($archiveName, $extension).'/watchexec';
    }
}
