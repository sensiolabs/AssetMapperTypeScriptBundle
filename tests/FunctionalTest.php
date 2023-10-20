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
        file_put_contents(__DIR__.'/fixtures/assets/typescript/main.js', <<<EOF
            console.log('Hello world');
            EOF
        );

        if (file_exists(__DIR__.'/fixtures/var')) {
            $filesystem = new Filesystem();
            $filesystem->remove(__DIR__.'/fixtures/var');
        }
    }

    protected function tearDown(): void
    {
        unlink(__DIR__.'/fixtures/assets/typescript/main.js');
    }

    public function testBuildJsIfUsed(): void
    {
        self::bootKernel();

        $assetMapper = self::getContainer()->get('asset_mapper');
        \assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset('typescript/main.js');
        $this->assertInstanceOf(MappedAsset::class, $asset);
        $this->assertStringContainsString('console.log(\'Hello world\');', $asset->content);
    }
}
