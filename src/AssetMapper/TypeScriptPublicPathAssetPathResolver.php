<?php

/*
 * This file is part of the SymfonyCasts SassBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        if (str_contains($path, '.ts')) {
            return str_replace('.ts', '.js', $path);
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
