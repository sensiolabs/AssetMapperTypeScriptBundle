<?php

namespace Sensiolabs\TypeScriptBundle\AssetMapper;

use Symfony\Component\AssetMapper\Path\PublicAssetsPathResolverInterface;

class TypeScriptPublicPathAssetPathResolver implements PublicAssetsPathResolverInterface
{
    public function __construct(private readonly PublicAssetsPathResolverInterface $decorator)
    {
    }

    public function resolvePublicPath(string $logicalPath): string
    {
        $path = $this->decorator->resolvePublicPath($logicalPath);

        if (str_ends_with($path, '.ts')) {
            return substr($path, 0, -3).'js';
        }

        return $path;
    }

    public function getPublicFilesystemPath(): string
    {
        $path = $this->decorator->getPublicFilesystemPath();

        if (str_contains($path, '.ts')) {
            return str_replace('.ts', '.js', $path);
        }

        return $path;
    }
}
