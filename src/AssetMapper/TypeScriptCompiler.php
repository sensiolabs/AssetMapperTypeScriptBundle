<?php

namespace Sensiolabs\TypeScriptBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

class TypeScriptCompiler implements AssetCompilerInterface
{
    private Filesystem $fileSystem;

    /**
     * @param list<string> $typeScriptFilesPaths
     */
    public function __construct(
        private readonly array $typeScriptFilesPaths,
        private readonly string $jsPathDirectory,
        private readonly string $projectRootDir,
    ) {
        $this->fileSystem = new Filesystem();
    }

    public function supports(MappedAsset $asset): bool
    {
        $realSourcePath = realpath($asset->sourcePath);
        if (false === $realSourcePath) {
            return false;
        }
        if (!str_ends_with($realSourcePath, '.ts')) {
            return false;
        }
        foreach ($this->typeScriptFilesPaths as $path) {
            $realTypeScriptPath = realpath($path);
            if (false === $realTypeScriptPath) {
                throw new \Exception(sprintf('The TypeScript directory "%s" does not exist', $path));
            }
            // If the asset matches one of the TypeScript files source paths
            if ($realSourcePath === $realTypeScriptPath) {
                return true;
            }
            // If the asset is in a directory (or subdirectory) of one of the TypeScript directory source paths
            if (is_dir($realTypeScriptPath) && !str_starts_with($this->fileSystem->makePathRelative($realSourcePath, $realTypeScriptPath), '../')) {
                return true;
            }
        }

        throw new \Exception(sprintf('The TypeScript file "%s" is not in the TypeScript files paths. Check the asset path or your "sensiolabs_typescript.source_dir" in your config', $realSourcePath));
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $realSourcePath = realpath($asset->sourcePath);
        if (false === $realSourcePath) {
            throw new \Exception(sprintf('The TypeScript file "%s" does not exist', $asset->sourcePath));
        }
        foreach ($this->typeScriptFilesPaths as $typeScriptFilesPath) {
            $realTypeScriptPath = realpath($typeScriptFilesPath);
            if (false === $realTypeScriptPath) {
                throw new \Exception(sprintf('The TypeScript directory "%s" does not exist', $typeScriptFilesPath));
            }
            if (str_starts_with($realSourcePath, $realTypeScriptPath)) {
                $fileName = basename($realSourcePath, '.ts');
                $subPath = trim($this->fileSystem->makePathRelative(\dirname($realSourcePath), $this->projectRootDir), '/');
                $jsFile = $this->jsPathDirectory.'/'.$subPath.'/'.$fileName.'.js';
                break;
            }
        }

        if (!isset($jsFile)) {
            throw new \Exception(sprintf('The TypeScript file "%s" is not in the TypeScript files paths. Check the asset path or your "sensiolabs_typescript.source_dir" in your config', $asset->sourcePath));
        }
        $asset->addFileDependency($jsFile);

        if (($content = file_get_contents($jsFile)) === false) {
            throw new \RuntimeException('The file '.$jsFile.' doesn\'t exist, run php bin/console typescript:build');
        }

        return $content;
    }
}
