<?php
//
use Sensiolabs\TypeScriptBundle\AssetMapper\TypeScriptCompiler;
use Sensiolabs\TypeScriptBundle\AssetMapper\TypeScriptPublicPathAssetPathResolver;
use Sensiolabs\TypeScriptBundle\Command\TypeScriptBuildCommand;
use Sensiolabs\TypeScriptBundle\EventListener\PreAssetsCompileListener;
use Sensiolabs\TypeScriptBundle\TypeScriptBuilder;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('typescript.builder', TypeScriptBuilder::class)
            ->args([
                abstract_arg('path to typescript files'),
                abstract_arg('path to compiled directory'),
                param('kernel.project_dir'),
                abstract_arg('path to the swc binary'),
            ])
        ->set('typescript.command.build', TypeScriptBuildCommand::class)
            ->args([
                service('typescript.builder')
            ])
            ->tag('console.command')
        ->set('typescript.js_asset_compiler', TypeScriptCompiler::class)
            ->tag('asset_mapper.compiler', [
                // A priority needs to be set to ensure that the TypeScript compiler is called before the JavaScript compiler
                'priority' => 10
            ])
            ->args([
                abstract_arg('path to typescript source dir'),
                abstract_arg('path to typescript output directory'),
                service('typescript.builder'),
            ])
        ->set('typescript.public_asset_path_resolver', TypeScriptPublicPathAssetPathResolver::class)
            ->decorate('asset_mapper.public_assets_path_resolver')
            ->args([
                service('.inner')
            ])
        ->set('typescript.pre_assets_compile_listener', PreAssetsCompileListener::class)
            ->args([service('typescript.builder')])
            ->tag('kernel.event_listener', [
                'event' => PreAssetsCompileEvent::class,
                'method' => '__invoke'
            ])
    ;
};
