<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WatchexecBinaryFactory
{
    public const VERSION = '1.20.5';
    private const WATCHEXEC_RELEASE_URL_PATTERN = 'https://github.com/watchexec/watchexec/releases/download/cli-v%s/%s';

    public function __construct(
        private readonly string $binaryDownloadDir,
        private ?HttpClientInterface $httpClient = null,
        private ?OutputInterface $output = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function getBinaryFromPath($pathToExecutable): WatchexecBinary
    {
        return new WatchexecBinary($pathToExecutable);
    }

    public function getBinaryFromServerSpecs($os, $machine, $kernel): WatchexecBinary
    {
        $binaryName = self::getBinaryNameFromServerSpecs($os, $machine, $kernel);
        if (!file_exists($this->binaryDownloadDir.'/'.$binaryName)) {
            $this->downloadAndExtract($binaryName);
        }

        return $this->getBinaryFromPath($this->binaryDownloadDir.'/'.$binaryName.'/watchexec');
    }

    public function setOutput(?SymfonyStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setHttpClient(HttpClientInterface $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    public static function getBinaryArchiveName($binaryName): string
    {
        $extension = str_contains($binaryName, 'windows') ? '.zip' : '.tar.xz';

        return $binaryName.$extension;
    }

    public static function getBinaryNameFromServerSpecs(
        $os,
        $machine,
        $kernel,
    ) {
        list($os, $machine, $kernel) = [strtolower($os), strtolower($machine), strtolower($kernel)];
        if (str_contains($os, 'darwin')) {
            if ('aarch64' === $machine) {
                return sprintf('watchexec-%s-%s', self::VERSION, 'aarch64-apple-darwin');
            } elseif ('x86_64' === $machine) {
                return sprintf('watchexec-%s-%s', self::VERSION, 'x86_64-apple-darwin');
            }
        }
        if (str_contains($os, 'linux')) {
            $kernelVersion = str_contains($kernel, 'musl') ? 'musl' : 'gnu';
            if ('arm64' === $machine || 'aarch64' === $machine) {
                return sprintf('watchexec-%s-%s', self::VERSION, 'aarch64-unknown-linux-'.$kernelVersion);
            } elseif ('x86_64' === $machine) {
                return sprintf('watchexec-%s-%s', self::VERSION, 'x86_64-unknown-linux-'.$kernelVersion);
            }
        }
        if (str_contains($os, 'win')) {
            if ('x86_64' === $machine) {
                return sprintf('watchexec-%s-%s', self::VERSION, 'x86_64-pc-windows-msvc');
            }
        }

        throw new \Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }

    private function downloadAndExtract($binaryName): void
    {
        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }
        $archiveName = self::getBinaryArchiveName($binaryName);
        $targetPath = $this->binaryDownloadDir.'/'.$archiveName;
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        $url = sprintf(self::WATCHEXEC_RELEASE_URL_PATTERN, self::VERSION, $archiveName);

        if ($this->output?->isVerbose()) {
            $this->output?->note(sprintf('Downloading Watchexec binary from "%s" to "%s"...', $url, $targetPath));
        } else {
            $this->output?->note('Downloading Watchexec binary ...');
        }

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

        if ($this->output?->isVerbose()) {
            $this->output?->note(sprintf('Extracting Watchexec binary from "%s"...', $targetPath));
        }

        $extractProcess = new Process(['tar', '-xf', $targetPath]);
        $extractProcess->setWorkingDirectory($this->binaryDownloadDir);
        $extractProcess->run();
        unlink($targetPath);
        chmod($this->binaryDownloadDir.'/'.$binaryName.'/watchexec', 7770);
    }
}
