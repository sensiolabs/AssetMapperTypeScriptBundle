<?php

namespace Sensiolabs\TypescriptBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TypeScriptBinary
{
    private const VERSION = 'v1.3.92';
    private const SWC_RELEASE_URL_PATTERN = 'https://github.com/swc-project/swc/releases/download/%s/%s';

    public function __construct(
        private readonly string      $binaryDownloadDir,
        private readonly ?string     $binaryPath,
        private ?SymfonyStyle        $output = null,
        private ?HttpClientInterface $httpClient = null,
        private ?string $binaryName = null,
    )
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * @param array<string> $args
     */
    public function createProcess(array $args): Process
    {
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
        $targetPath = $this->getDefaultBinaryPath();
        if (file_exists($targetPath)) {
            return;
        }

        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }
        if (null === $this->httpClient) {
            throw new \LogicException('The HttpClientInterface is not available. Try running "composer require symfony/http-client".');
        }

        $url = sprintf(self::SWC_RELEASE_URL_PATTERN, self::VERSION, $this->getBinaryName());
        $this->output?->note(sprintf('Downloading TypeScript binary to %s...', $targetPath));

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
        chmod($targetPath, 7770);
        $progressBar?->finish();
        $this->output?->writeln('');
    }

    private function getBinaryName(): string
    {
        if (null !== $this->binaryName) {
            return $this->binaryName;
        }
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));

        if (str_contains($os, 'darwin')) {
            if ('arm64' === $machine) {
                return $this->binaryName = 'swc-darwin-arm64';
            }

            if ('x86_64' === $machine) {
                return $this->binaryName = 'swc-darwin-x64';
            }

            throw new \Exception(sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'linux')) {
            $kernel = strtolower(php_uname('r'));
            $kernelVersion = str_contains($kernel, 'musl') ? 'musl' : 'gnu';
            if ('arm64' === $machine || 'aarch64' === $machine) {
                return $this->binaryName = 'swc-linux-arm64-' . $kernelVersion;
            }
            if ('x86_64' === $machine) {
                return $this->binaryName = 'swc-linux-x64-' . $kernelVersion;
            }

            throw new \Exception(sprintf('No matching machine found for Linux platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'win')) {
            if ('x86_64' === $machine) {
                return $this->binaryName = 'swc-win32-x64-msvc';
            }
            if ('amd64' === $machine) {
                return $this->binaryName = 'swc-win32-arm64-msvc';
            }
            if ('i586' === $machine) {
                return $this->binaryName = 'swc-win32-ia32-msvc';
            }

            throw new \Exception(sprintf('No matching machine found for Windows platform (Machine: %s).', $machine));
        }

        throw new \Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }

    private function getDefaultBinaryPath(): string
    {
        return $this->binaryDownloadDir . '/' . $this->getBinaryName();
    }
}
