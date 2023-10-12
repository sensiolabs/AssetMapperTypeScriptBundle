<?php
//
use Sensiolabs\TypescriptBundle\AssetMapper\TypeScriptCompiler;
use Sensiolabs\TypescriptBundle\AssetMapper\TypeScriptPublicPathAssetPathResolver;
use Sensiolabs\TypescriptBundle\Command\TypeScriptCompileCommand;
use Sensiolabs\TypescriptBundle\TypeScriptBuilder;
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
            abstract_arg('path to binary'),
            abstract_arg('embed sourcemap'),
            abstract_arg('swc configuration file'),
        ])
        ->set('typescript.command.build', TypeScriptCompileCommand::class)
        ->args([
            service('typescript.builder')
        ])
        ->tag('console.command')
        ->set('typescript.js_asset_compiler', TypeScriptCompiler::class)
        ->tag('asset_mapper.compiler', [
            'priority' => 9
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
        ]);
;
};
