<?php

/*
 * This file is part of the SymfonyCasts SassBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensiolabs\TypeScriptBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

class FunctionalTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir(__DIR__.'/fixtures/var');
        $filesystem->dumpFile(__DIR__.'/fixtures/var/typescript/assets/typescript/main.js', <<<EOF
            var greeting = "Hello, World!";
            console.log(greeting);
            EOF
        );
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if (file_exists(__DIR__.'/fixtures/var')) {
            $filesystem->remove(__DIR__.'/fixtures/var');
        }
    }

    public function testBuildJsIfUsed(): void
    {
        self::bootKernel();

        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset('typescript/main.ts');
        $this->assertInstanceOf(MappedAsset::class, $asset);
        $this->assertStringContainsString(<<<EOF
            var greeting = "Hello, World!";
            console.log(greeting);
            EOF, $asset->content);
    }
}
