<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Command\TypeScriptTypesInstallCommand;
use Sensiolabs\TypeScriptBundle\Types\TypeDefinitionsInstaller;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntries;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class TypeScriptTypesInstallCommandTest extends TestCase
{
    private string $projectDir = __DIR__.'/../fixtures/var/project';

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

    public function testReportsAnEmptyImportmap(): void
    {
        $tester = $this->createTester([], []);

        $this->assertSame(0, $tester->execute([]));
        $this->assertStringContainsString('No remote JavaScript package found in the importmap.', $tester->getDisplay());
    }

    public function testListsTheInstalledAndMissingPackages(): void
    {
        $tester = $this->createTester([
            $this->createRemoteEntry('typed', '1.0.0'),
            $this->createRemoteEntry('untyped', '2.0.0'),
        ], [
            'https://data.jsdelivr.com/v1/packages/npm/typed@1.0.0?structure=flat' => ['files' => [['name' => '/package.json'], ['name' => '/index.d.ts']]],
            'https://cdn.jsdelivr.net/npm/typed@1.0.0/package.json' => ['types' => 'index.d.ts'],
            'https://cdn.jsdelivr.net/npm/typed@1.0.0/index.d.ts' => 'export {};',
        ]);

        $this->assertSame(0, $tester->execute([]));
        $display = $tester->getDisplay();
        $this->assertStringContainsString('typed', $display);
        $this->assertStringContainsString('typed@1.0.0', $display);
        $this->assertMatchesRegularExpression('/untyped\s+not found/', $display);
        $this->assertStringContainsString('Declaration files installed.', $display);
        $this->assertStringContainsString('"extends": "./var/typescript-types/tsconfig.importmap.json"', $display);
        $this->assertStringContainsString('No declaration files found for "untyped"', $display);
    }

    public function testWarnsWhenNoDeclarationFilesWereFound(): void
    {
        $tester = $this->createTester([$this->createRemoteEntry('untyped', '2.0.0')], []);

        $this->assertSame(0, $tester->execute([]));
        $display = $tester->getDisplay();
        $this->assertStringContainsString('No declaration files were found for any package.', $display);
        $this->assertStringNotContainsString('Declaration files installed.', $display);
    }

    /**
     * @param list<ImportMapEntry>               $entries
     * @param array<string, array<mixed>|string> $responses
     */
    private function createTester(array $entries, array $responses): CommandTester
    {
        $configReader = $this->createMock(ImportMapConfigReader::class);
        $configReader->method('getEntries')->willReturn(new ImportMapEntries($entries));

        $httpClient = new MockHttpClient(static function (string $method, string $url) use ($responses): MockResponse {
            if (!isset($responses[$url])) {
                return new MockResponse('{}', ['http_code' => 404]);
            }

            return new MockResponse(\is_array($responses[$url]) ? json_encode($responses[$url], \JSON_THROW_ON_ERROR) : $responses[$url]);
        });

        return new CommandTester(new TypeScriptTypesInstallCommand(
            $configReader,
            new TypeDefinitionsInstaller($this->projectDir.'/var/typescript-types', $httpClient),
            $this->projectDir,
        ));
    }

    private function createRemoteEntry(string $importName, string $version): ImportMapEntry
    {
        return ImportMapEntry::createRemote($importName, ImportMapType::JS, '/path', $version, $importName, false);
    }
}
