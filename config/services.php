<?php

use Sensiolabs\TypeScriptBundle\AssetMapper\TypeScriptCompiler;
use Sensiolabs\TypeScriptBundle\AssetMapper\TypeScriptPublicPathAssetPathResolver;
use Sensiolabs\TypeScriptBundle\Command\TypeScriptBuildCommand;
use Sensiolabs\TypeScriptBundle\EventListener\PreAssetsCompileListener;
use Sensiolabs\TypeScriptBundle\TypeScriptBuilder;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('sensiolabs_typescript.builder', TypeScriptBuilder::class)
            ->args([
                abstract_arg('path to typescript files'),
                abstract_arg('path to compiled directory'),
                param('kernel.project_dir'),
                abstract_arg('path to the binaries download directory'),
                abstract_arg('path to the swc binary'),
                abstract_arg('swc configuration file'),
                abstract_arg('swc version'),
            ])
        ->set('sensiolabs_typescript.command.build', TypeScriptBuildCommand::class)
            ->args([
                service('sensiolabs_typescript.builder')
            ])
            ->tag('console.command')
        ->set('sensiolabs_typescript.js_asset_compiler', TypeScriptCompiler::class)
            ->tag('asset_mapper.compiler', [
                // A priority needs to be set to ensure that the TypeScript compiler is called before the JavaScript compiler
                'priority' => 10
            ])
            ->args([
                abstract_arg('path to typescript source dir'),
                abstract_arg('path to typescript output directory'),
                service('sensiolabs_typescript.builder'),
            ])
        ->set('sensiolabs_typescript.public_asset_path_resolver', TypeScriptPublicPathAssetPathResolver::class)
            ->decorate('asset_mapper.public_assets_path_resolver')
            ->args([
                service('.inner')
            ])
        ->set('sensiolabs_typescript.pre_assets_compile_listener', PreAssetsCompileListener::class)
            ->args([service('sensiolabs_typescript.builder')])
            ->tag('kernel.event_listener', [
                'event' => PreAssetsCompileEvent::class,
                'method' => '__invoke'
            ])
    ;
};
