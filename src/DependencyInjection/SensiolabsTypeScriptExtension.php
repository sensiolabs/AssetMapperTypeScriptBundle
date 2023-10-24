<?php

namespace Sensiolabs\TypeScriptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SensiolabsTypeScriptExtension extends Extension implements ConfigurationInterface
{
    public function getAlias(): string
    {
        return 'sensiolabs_typescript';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('sensiolabs_typescript.builder')
            ->replaceArgument(0, $config['source_dir'])
            ->replaceArgument(1, '%kernel.project_dir%/var/typescript')
            ->replaceArgument(3, $config['binary_download_dir'])
            ->replaceArgument(4, $config['swc_binary'])
            ->replaceArgument(5, $config['config_file'])
        ;
        $container->findDefinition('sensiolabs_typescript.js_asset_compiler')
            ->replaceArgument(0, $config['source_dir'])
            ->replaceArgument(1, '%kernel.project_dir%/var/typescript')
            ->replaceArgument(2, '%kernel.project_dir%')
        ;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sensiolabs_typescript');

        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->arrayNode('source_dir')
                    ->info('Path to your TypeScript directories')
                    ->cannotBeEmpty()
                    ->scalarPrototype()
                        ->end()
                    ->defaultValue(['%kernel.project_dir%/assets'])
                ->end()
                ->scalarNode('binary_download_dir')
                    ->info('The directory where the SWC and Watchexec binaries will be downloaded')
                    ->defaultValue('%kernel.project_dir%/var')
                ->end()
                ->scalarNode('swc_binary')
                    ->info('The SWC binary to use')
                    ->defaultNull()
                ->end()
                ->scalarNode('swc_config_file')
                    ->info('Path to .swcrc configuration file to use')
                    ->defaultValue('%kernel.project_dir%/.swcrc')
                ->end()
                ->scalarNode('watchexec_binary')
                    ->info('The Watchexec binary to use')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
