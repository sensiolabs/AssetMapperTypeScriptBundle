<?php

namespace Sensiolabs\TypeScriptBundle\DependencyInjection;

use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
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
            ->replaceArgument(1, $config['compile_dir'])
            ->replaceArgument(3, $config['binary_download_dir'])
            ->replaceArgument(4, $config['swc_binary'])
            ->replaceArgument(5, $config['swc_config_file'])
            ->replaceArgument(6, $config['swc_version'])
        ;
        $container->findDefinition('sensiolabs_typescript.types_installer')
            ->replaceArgument(0, $config['types_dir'])
        ;
        // The importmap entries API was introduced in Symfony 6.4
        if (!class_exists(ImportMapConfigReader::class)) {
            $container->removeDefinition('sensiolabs_typescript.types_installer');
            $container->removeDefinition('sensiolabs_typescript.command.types_install');
        }
        $container->findDefinition('sensiolabs_typescript.js_asset_compiler')
            ->replaceArgument(0, $config['source_dir'])
            ->replaceArgument(1, $config['compile_dir'])
            ->replaceArgument(2, '%kernel.project_dir%')
        ;
    }

    /**
     * @param array<array<mixed>> $configs
     */
    public function getConfiguration(array $configs, ContainerBuilder $container): ConfigurationInterface
    {
        return $this;
    }

    /**
     * @return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sensiolabs_typescript');

        $rootNode = $treeBuilder->getRootNode();

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
                    ->info('The directory where the SWC binary will be downloaded')
                    ->defaultValue('%kernel.project_dir%/var')
                ->end()
                ->scalarNode('compile_dir')
                    ->info('The directory where the compiled JavaScript files will be stored')
                    ->defaultValue('%kernel.project_dir%/var/typescript')
                ->end()
                ->scalarNode('types_dir')
                    ->info('The directory where the TypeScript declaration files of the importmap packages will be downloaded')
                    ->defaultValue('%kernel.project_dir%/var/typescript-types')
                ->end()
                ->scalarNode('swc_binary')
                    ->info('The SWC binary to use')
                    ->defaultNull()
                ->end()
                ->scalarNode('swc_config_file')
                    ->info('Path to .swcrc configuration file to use')
                    ->defaultValue('%kernel.project_dir%/.swcrc')
                ->end()
                ->scalarNode('swc_version')
                    ->info('The SWC version to use')
                    ->defaultValue('v1.3.92')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
