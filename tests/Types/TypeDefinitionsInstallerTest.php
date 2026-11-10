<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Types;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Types\TypeDefinitionsInstaller;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TypeDefinitionsInstallerTest extends TestCase
{
    private string $typesDir = __DIR__.'/../fixtures/var/typescript-types';

    /**
     * @var list<string>
     */
    private array $requestedUrls = [];

    protected function setUp(): void
    {
        if (!class_exists(ImportMapConfigReader::class)) {
            $this->markTestSkipped('The importmap entries API requires symfony/asset-mapper 6.4+.');
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(__DIR__.'/../fixtures/var');
    }

    public function testInstallsBundledDeclarationFilesAndDependencies(): void
    {
        $installer = $this->createInstaller([
            'https://data.jsdelivr.com/v1/packages/npm/chart.js@4.4.1?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/dist/chart.js'],
                ['name' => '/dist/types.d.ts'],
                ['name' => '/dist/types.d.cts'],
                ['name' => '/auto/auto.d.ts'],
                ['name' => '/../../evil.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/package.json' => [
                'exports' => [
                    '.' => ['types' => './dist/types.d.ts', 'import' => './dist/chart.js'],
                    './auto' => ['types' => './auto/auto.d.ts', 'import' => './auto/auto.js'],
                ],
                'dependencies' => ['@kurkle/color' => '^0.3.0'],
            ],
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/types.d.ts' => 'export declare class Chart {}',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/auto/auto.d.ts' => 'export * from "../dist/types";',
            'https://data.jsdelivr.com/v1/packages/npm/@kurkle/color/resolved?specifier=%5E0.3.0' => ['version' => '0.3.2'],
            'https://data.jsdelivr.com/v1/packages/npm/@kurkle/color@0.3.2?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/dist/color.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/@kurkle/color@0.3.2/package.json' => ['types' => 'dist/color.d.ts'],
            'https://cdn.jsdelivr.net/npm/@kurkle/color@0.3.2/dist/color.d.ts' => 'export type Color = string;',
        ]);

        $report = $installer->install([
            $this->createRemoteEntry('chart.js', '4.4.1'),
            $this->createRemoteEntry('chart.js/auto', '4.4.1'),
        ]);

        $this->assertSame([
            'chart.js' => ['package' => 'chart.js@4.4.1', 'path' => './node_modules/chart.js/dist/types.d.ts'],
            'chart.js/auto' => ['package' => 'chart.js@4.4.1', 'path' => './node_modules/chart.js/auto/auto.d.ts'],
        ], $report->imports);
        $this->assertSame([], $report->getMissingImports());

        $this->assertFileExists($this->typesDir.'/node_modules/chart.js/dist/types.d.ts');
        $this->assertFileExists($this->typesDir.'/node_modules/@kurkle/color/dist/color.d.ts');
        $this->assertFileDoesNotExist($this->typesDir.'/node_modules/chart.js/dist/types.d.cts');
        $this->assertNotContains('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/../../evil.d.ts', $this->requestedUrls);

        $this->assertJsonStringEqualsJsonString(json_encode(['compilerOptions' => ['paths' => [
            'chart.js' => ['./node_modules/chart.js/dist/types.d.ts'],
            'chart.js/auto' => ['./node_modules/chart.js/auto/auto.d.ts'],
        ]]], \JSON_THROW_ON_ERROR), (string) file_get_contents($report->tsConfigPath));
    }

    public function testFallsBackToDefinitelyTyped(): void
    {
        $installer = $this->createInstaller([
            'https://data.jsdelivr.com/v1/packages/npm/@hotwired/turbo@8.0.12?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/dist/turbo.es2017-esm.js'],
            ]],
            'https://data.jsdelivr.com/v1/packages/npm/@types/hotwired__turbo/resolved?specifier=8.0' => ['version' => '8.0.16'],
            'https://data.jsdelivr.com/v1/packages/npm/@types/hotwired__turbo@8.0.16?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/index.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/@types/hotwired__turbo@8.0.16/package.json' => ['types' => 'index.d.ts'],
            'https://cdn.jsdelivr.net/npm/@types/hotwired__turbo@8.0.16/index.d.ts' => 'export function visit(location: string): void;',
        ]);

        $report = $installer->install([$this->createRemoteEntry('@hotwired/turbo', '8.0.12')]);

        $this->assertSame([
            '@hotwired/turbo' => ['package' => '@types/hotwired__turbo@8.0.16', 'path' => './node_modules/@types/hotwired__turbo/index.d.ts'],
        ], $report->imports);
    }

    public function testReportsPackagesWithoutDeclarationFiles(): void
    {
        $installer = $this->createInstaller([
            'https://data.jsdelivr.com/v1/packages/npm/untyped@1.2.3?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/index.js'],
            ]],
            'https://data.jsdelivr.com/v1/packages/npm/@types/untyped/resolved?specifier=1.2' => ['version' => null],
            'https://data.jsdelivr.com/v1/packages/npm/@types/untyped/resolved?specifier=1' => ['version' => null],
        ]);

        $report = $installer->install([
            ImportMapEntry::createLocal('app', ImportMapType::JS, './assets/app.js', true),
            ImportMapEntry::createRemote('bootstrap/dist/css/bootstrap.min.css', ImportMapType::CSS, '/path', '5.3.3', 'bootstrap/dist/css/bootstrap.min.css', false),
            $this->createRemoteEntry('untyped', '1.2.3'),
        ]);

        $this->assertSame(['untyped' => null], $report->imports);
        $this->assertSame(['untyped'], $report->getMissingImports());
        $this->assertJsonStringEqualsJsonString('{"compilerOptions": {"paths": {}}}', (string) file_get_contents($report->tsConfigPath));
    }

    public function testPinnedVersionWinsOverDependencyRange(): void
    {
        $installer = $this->createInstaller([
            'https://data.jsdelivr.com/v1/packages/npm/chart.js@4.4.1?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/dist/types.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/package.json' => ['types' => 'dist/types.d.ts', 'dependencies' => ['@kurkle/color' => '^0.3.0']],
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/types.d.ts' => 'export declare class Chart {}',
            'https://data.jsdelivr.com/v1/packages/npm/@kurkle/color/resolved?specifier=%5E0.3.0' => ['version' => '0.3.4'],
            'https://data.jsdelivr.com/v1/packages/npm/@kurkle/color@0.3.2?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/dist/color.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/@kurkle/color@0.3.2/package.json' => ['types' => 'dist/color.d.ts'],
            'https://cdn.jsdelivr.net/npm/@kurkle/color@0.3.2/dist/color.d.ts' => 'export type Color = string;',
        ]);

        $report = $installer->install([
            $this->createRemoteEntry('chart.js', '4.4.1'),
            $this->createRemoteEntry('@kurkle/color', '0.3.2'),
        ]);

        $this->assertSame('@kurkle/color@0.3.2', $report->imports['@kurkle/color']['package'] ?? null);
        $this->assertNotContains('https://data.jsdelivr.com/v1/packages/npm/@kurkle/color@0.3.4?structure=flat', $this->requestedUrls);
    }

    public function testKeepsPreviousDeclarationFilesWhenTheDownloadFails(): void
    {
        $this->createInstaller([
            'https://data.jsdelivr.com/v1/packages/npm/left-pad@1.3.0?structure=flat' => ['files' => [
                ['name' => '/package.json'],
                ['name' => '/index.d.ts'],
            ]],
            'https://cdn.jsdelivr.net/npm/left-pad@1.3.0/package.json' => ['types' => 'index.d.ts'],
            'https://cdn.jsdelivr.net/npm/left-pad@1.3.0/index.d.ts' => 'export default function leftPad(): string;',
        ])->install([$this->createRemoteEntry('left-pad', '1.3.0')]);
        $tsConfig = file_get_contents($this->typesDir.'/'.TypeDefinitionsInstaller::TSCONFIG_FILENAME);

        $failingInstaller = new TypeDefinitionsInstaller($this->typesDir, new MockHttpClient(new MockResponse('', ['http_code' => 500])));
        try {
            $failingInstaller->install([$this->createRemoteEntry('left-pad', '1.3.0')]);
            $this->fail('The installation should have failed.');
        } catch (\Throwable) {
        }

        $this->assertFileExists($this->typesDir.'/node_modules/left-pad/index.d.ts');
        $this->assertSame($tsConfig, file_get_contents($this->typesDir.'/'.TypeDefinitionsInstaller::TSCONFIG_FILENAME));
        $this->assertDirectoryDoesNotExist($this->typesDir.'/node_modules.tmp');
    }

    public function testRefusesToReplaceANodeModulesDirectoryItDidNotGenerate(): void
    {
        (new Filesystem())->dumpFile($this->typesDir.'/node_modules/some-package/index.js', '');

        try {
            $this->createInstaller([])->install([]);
            $this->fail('The installation should have been refused.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('was not generated by this bundle', $e->getMessage());
        }

        $this->assertFileExists($this->typesDir.'/node_modules/some-package/index.js');
    }

    /**
     * @param array<string, array<mixed>|string> $responses JSON bodies as arrays, any other body as a string
     */
    private function createInstaller(array $responses): TypeDefinitionsInstaller
    {
        $httpClient = new MockHttpClient(function (string $method, string $url) use ($responses): MockResponse {
            $this->requestedUrls[] = $url;

            if (!isset($responses[$url])) {
                return new MockResponse('{}', ['http_code' => 404]);
            }

            return new MockResponse(\is_array($responses[$url]) ? json_encode($responses[$url], \JSON_THROW_ON_ERROR) : $responses[$url]);
        });

        return new TypeDefinitionsInstaller($this->typesDir, $httpClient);
    }

    private function createRemoteEntry(string $importName, string $version): ImportMapEntry
    {
        return ImportMapEntry::createRemote($importName, ImportMapType::JS, '/path', $version, $importName, false);
    }
}
