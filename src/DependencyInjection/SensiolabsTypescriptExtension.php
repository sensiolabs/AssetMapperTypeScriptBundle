<?php

namespace Sensiolabs\TypescriptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SensiolabsTypescriptExtension extends Extension implements ConfigurationInterface
{

    private bool $isDebug;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->isDebug = $container->getParameter('kernel.debug');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('typescript.builder')
            ->replaceArgument(0, $config['source_dir'])
            ->replaceArgument(1, '%kernel.project_dir%/var/compiled_js')
            ->replaceArgument(3, $config['binary'])
            ->replaceArgument(4, $config['embed_sourcemap'])
            ->replaceArgument(5, $config['config_file']);

        $container->findDefinition('typescript.js_asset_compiler')
            ->replaceArgument(0, $config['source_dir'])
            ->replaceArgument(1, '%kernel.project_dir%/var/compiled_js')
            ->replaceArgument(2, '%kernel.project_dir%');
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
                    ->defaultValue(['%kernel.project_dir%/assets/typescript'])
                    ->end()
                ->scalarNode('binary')
                    ->info('The TypeScript compiler binary to use')
                    ->defaultNull()
                    ->end()
                ->scalarNode('embed_sourcemap')
                    ->info('Whether to embed the sourcemap in the compiled CSS. By default, enabled only when debug mode is on.')
                    ->defaultValue($this->isDebug)
                    ->end()
                ->scalarNode('config_file')
                    ->info('Path to .swcrc configuration file to use')
                    ->defaultValue('%kernel.project_dir%/.swcrc')
                    ->end()
            ->end();

        return $treeBuilder;
    }
}
