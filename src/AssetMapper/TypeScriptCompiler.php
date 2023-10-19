<?php

/*
 * This file is part of the SymfonyCasts SassBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensiolabs\TypescriptBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

class TypeScriptCompiler implements AssetCompilerInterface
{
    private Filesystem $fileSystem;

    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $jsPathDirectory,
        private readonly string $projectRootDir,
    )
    {
        $this->fileSystem = new Filesystem();
    }

    public function supports(MappedAsset $asset): bool
    {
        if (!str_ends_with($asset->sourcePath, '.ts')) {
            return false;
        }
        foreach ($this->typeScriptFilesPaths as $path) {
            // If the asset matches one of the TypeScript files source paths
            if (realpath($asset->sourcePath) === realpath($path)) {
                return true;
            }
            // If the asset is in a directory (or subdirectory) of one of the TypeScript directory source paths
            if (is_dir($path) && !str_starts_with($this->fileSystem->makePathRelative(realpath($asset->sourcePath), realpath($path)), '../')) {
                return true;
            }
        }

        throw new \Exception(sprintf('The TypeScript file "%s" is not in the TypeScript files paths. Check the asset path or your "sensiolabs_typescript.source_dir" in your config', $asset->sourcePath));
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        foreach ($this->typeScriptFilesPaths as $typeScriptFilesPath) {
            if (str_starts_with($asset->sourcePath, $typeScriptFilesPath)) {
                $fileName = basename($asset->sourcePath, '.ts');
                $subPath = trim($this->fileSystem->makePathRelative(dirname($asset->sourcePath), $this->projectRootDir), '/');
                $jsFile = $this->jsPathDirectory . '/' . $subPath . '/' . $fileName . '.js';
                break;
            }
        }

        $asset->addFileDependency($jsFile);

        if (($content = file_get_contents($jsFile)) === false) {
            throw new \RuntimeException('The file ' . $jsFile . ' doesn\'t exist, run php bin/console typescript:build');
        }

        return $content;
    }
}
