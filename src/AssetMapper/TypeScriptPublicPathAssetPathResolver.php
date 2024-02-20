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
            return substr($path, 0, -3).'.js';
        }

        return $path;
    }

    public function getPublicFilesystemPath(): string
    {
        if (!method_exists($this->decorator, 'getPublicFilesystemPath')) {
            throw new \LogicException('The decorated resolver does not implement the "getPublicFilesystemPath" method.');
        }
        $path = $this->decorator->getPublicFilesystemPath();

        if (str_ends_with($path, '.ts')) {
            return substr($path, 0, -3).'.js';
        }

        return $path;
    }
}
