<?php

/*
 * This file is part of the SymfonyCasts SassBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensiolabs\TypeScriptBundle\Tests\fixtures;

use Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TypeScriptTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SensiolabsTypeScriptBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'foo',
            'test' => true,
            'http_method_override' => true,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/assets',
                ],
            ],
        ]);

        $container->loadFromExtension('sensiolabs_typescript', [
            'source_dir' => [__DIR__.'/assets'],
        ]);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/cache'.spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/logs'.spl_object_hash($this);
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
