<?php

namespace Sensiolabs\TypescriptBundle\Tests;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypescriptBundle\AssetMapper\TypeScriptCompiler;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

class TypeScriptCompilerTest extends TestCase
{
    private TypeScriptCompiler $compiler;
    private const FIXTURE_DIR = __DIR__ . '/fixtures';
    private const ASSETS_DIR = __DIR__ . '/fixtures/assets';
    public function setUp(): void
    {
        parent::setUp();
        $typeScriptFilesPaths = [realpath(self::ASSETS_DIR), realpath(self::ASSETS_DIR . '/../other_dir')];
        $compiledFilesPath = self::FIXTURE_DIR . '/var/typescript';
        mkdir($compiledFilesPath, 0777, true);
        $this->compiler = new TypeScriptCompiler(
            $typeScriptFilesPaths,
            $compiledFilesPath,
            realpath(self::FIXTURE_DIR)
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem())->remove(self::FIXTURE_DIR . '/var');
    }

    /**
     * @dataProvider provideAssetsForSupports
     */
    public function testSupports(MappedAsset $asset, $supports)
    {
        $this->assertEquals($supports, $this->compiler->supports($asset));
    }

    public static function provideAssetsForSupports(): iterable
    {
        yield 'file_in_the_dir' =>
            [
                new MappedAsset('typescript/main.ts', self::ASSETS_DIR . '/typescript/main.ts'),
                true
            ];
        yield 'file_not_in_the_dir' =>
            [
                new MappedAsset('app.js', self::ASSETS_DIR . '/../app.js'),
                false
            ];
        yield 'file_in_the_list' =>
        [
            new MappedAsset('custom.module.ts', self::ASSETS_DIR . '/typescript/dir/custom.module.ts'),
            true
        ];
        yield 'file_with_wrong_extension' =>
        [
            new MappedAsset('custom.js', self::ASSETS_DIR . '/typescript/custom.js'),
            false
        ];
        yield 'directory' =>
        [
            new MappedAsset('typescript/dir', self::ASSETS_DIR . '/typescript/dir'),
            false
        ];
    }

    public function testCompile(): void
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $asset = new MappedAsset('typescript/main.ts', realpath(self::ASSETS_DIR . '/typescript/main.ts'));
        $string = "console.log('This is a test');";
        $compiledFilePath = self::FIXTURE_DIR . '/var/typescript/assets/typescript/main.js';
        mkdir(dirname($compiledFilePath), 0777, true);
        file_put_contents($compiledFilePath, $string);
        $content = $this->compiler->compile('', $asset, $assetMapper);
        $this->assertEquals($string, $content);
    }
}
