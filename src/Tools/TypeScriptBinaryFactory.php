<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TypeScriptBinaryFactory
{
    private const SWC_RELEASE_URL_PATTERN = 'https://github.com/swc-project/swc/releases/download/%s/%s';
    private HttpClientInterface $httpClient;
    private SymfonyStyle $output;

    public function __construct(
        private readonly string $binaryDownloadDir,
        private readonly string $swcVersion,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function getBinaryFromPath(string $pathToExecutable): TypeScriptBinary
    {
        return new TypeScriptBinary($pathToExecutable);
    }

    public function getBinaryFromServerSpecs(string $os, string $machine, string $kernel): TypeScriptBinary
    {
        $binaryName = self::getBinaryNameFromServerSpecs($os, $machine, $kernel);
        if (!file_exists($this->binaryDownloadDir.'/'.$binaryName)) {
            $this->downloadAndExtract($binaryName);
        }

        return $this->getBinaryFromPath($this->binaryDownloadDir.'/'.$binaryName);
    }

    public function setOutput(SymfonyStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setHttpClient(HttpClientInterface $client): self
    {
        $this->httpClient = $client;

        return $this;
    }

    public static function getBinaryNameFromServerSpecs(
        string $os,
        string $machine,
        string $kernel,
    ): string {
        list($os, $machine, $kernel) = [strtolower($os), strtolower($machine), strtolower($kernel)];
        if (str_contains($os, 'darwin')) {
            if ('arm64' === $machine) {
                return 'swc-darwin-arm64';
            }

            if ('x86_64' === $machine) {
                return 'swc-darwin-x64';
            }

            throw new \Exception(\sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'linux')) {
            $kernelVersion = str_contains($kernel, 'musl') ? 'musl' : 'gnu';
            if ('arm64' === $machine || 'aarch64' === $machine) {
                return 'swc-linux-arm64-'.$kernelVersion;
            }
            if ('x86_64' === $machine) {
                return 'swc-linux-x64-'.$kernelVersion;
            }

            throw new \Exception(\sprintf('No matching machine found for Linux platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'win')) {
            if ('x86_64' === $machine || 'amd64' === $machine) {
                return 'swc-win32-x64-msvc.exe';
            }
            if ('arm64' === $machine) {
                return 'swc-win32-arm64-msvc.exe';
            }
            if ('i586' === $machine) {
                return 'swc-win32-ia32-msvc.exe';
            }

            throw new \Exception(\sprintf('No matching machine found for Windows platform (Machine: %s).', $machine));
        }

        throw new \Exception(\sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }

    private function downloadAndExtract(string $binaryName): void
    {
        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }
        $targetPath = $this->binaryDownloadDir.'/'.$binaryName;
        if (file_exists($targetPath)) {
            return;
        }
        $url = \sprintf(self::SWC_RELEASE_URL_PATTERN, $this->swcVersion, $binaryName);

        if ($this->output->isVerbose()) {
            $this->output->note(\sprintf('Downloading SWC binary from "%s" to "%s"...', $url, $targetPath));
        } else {
            $this->output->note('Downloading SWC binary ...');
        }

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output->createProgressBar($dlSize);
                }

                $progressBar->setProgress($dlNow);
            },
        ]);

        if (200 !== $statusCode = $response->getStatusCode()) {
            $exceptionMessage = \sprintf('Could not download SWC binary from "%s" (request responded with %d).', $url, $statusCode);
            if (404 === $statusCode) {
                $exceptionMessage .= \PHP_EOL.\sprintf('Check that the version "%s" defined in "sensiolabs_typescript.swc_version" exists.', $this->swcVersion);
            }
            throw new \Exception($exceptionMessage);
        }

        $fileHandler = fopen($targetPath, 'w');
        if (false === $fileHandler) {
            throw new \Exception(\sprintf('Could not open file "%s" for writing.', $targetPath));
        }
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        fclose($fileHandler);
        $progressBar?->finish();
        $this->output->writeln('');

        chmod($this->binaryDownloadDir.'/'.$binaryName, 0777);
    }
}
