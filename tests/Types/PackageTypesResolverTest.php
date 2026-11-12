<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Types\PackageTypesResolver;

class PackageTypesResolverTest extends TestCase
{
    /**
     * @param array<string, array<string, mixed>> $manifests
     * @param list<string>                        $files
     */
    #[DataProvider('provideResolutions')]
    public function testResolve(?string $expected, array $manifests, array $files, string $subpath = '.'): void
    {
        $this->assertSame($expected, PackageTypesResolver::resolve($manifests, array_fill_keys($files, true), $subpath));
    }

    /**
     * @return iterable<string, array{string|null, array<string, array<string, mixed>>, list<string>, 2?: string}>
     */
    public static function provideResolutions(): iterable
    {
        yield 'wildcard exports pattern' => [
            'dist/plugins/bar.d.ts',
            ['package.json' => ['exports' => ['./plugins/*' => ['types' => './dist/plugins/*.d.ts']]]],
            ['package.json', 'dist/plugins/bar.d.ts'],
            './plugins/bar',
        ];
        yield 'wildcard exports pattern with the longest prefix' => [
            'special/bar.d.ts',
            ['package.json' => ['exports' => [
                './*' => './generic/*.d.ts',
                './plugins/*' => './plugins/*.d.ts',
                './plugins/special/*' => './special/*.d.ts',
            ]]],
            ['package.json', 'generic/plugins/special/bar.d.ts', 'plugins/special/bar.d.ts', 'special/bar.d.ts'],
            './plugins/special/bar',
        ];
        yield 'subpath package.json' => [
            'sub/dist/sub.d.ts',
            ['package.json' => [], 'sub/package.json' => ['types' => 'dist/sub.d.ts']],
            ['package.json', 'sub/package.json', 'sub/dist/sub.d.ts'],
            './sub',
        ];
        yield 'subpath package.json main field' => [
            'sub/dist/sub.d.ts',
            ['package.json' => [], 'sub/package.json' => ['main' => 'dist/sub.js']],
            ['package.json', 'sub/package.json', 'sub/dist/sub.d.ts'],
            './sub',
        ];
        yield 'subpath without exports' => [
            'sub/index.d.ts',
            ['package.json' => []],
            ['package.json', 'sub/index.d.ts'],
            './sub',
        ];
        yield 'types field' => [
            'dist/types.d.ts',
            ['package.json' => ['types' => 'dist/types.d.ts', 'main' => 'dist/main.js']],
            ['package.json', 'dist/types.d.ts', 'dist/main.d.ts'],
        ];
        yield 'typings field' => [
            'dist/typings.d.ts',
            ['package.json' => ['typings' => './dist/typings.d.ts']],
            ['package.json', 'dist/typings.d.ts'],
        ];
        yield 'module field' => [
            'dist/module.d.ts',
            ['package.json' => ['module' => 'dist/module.js', 'main' => 'dist/main.js']],
            ['package.json', 'dist/module.d.ts', 'dist/main.d.ts'],
        ];
        yield 'main field' => [
            'dist/main.d.ts',
            ['package.json' => ['main' => 'dist/main.js']],
            ['package.json', 'dist/main.d.ts'],
        ];
        yield 'index.js fallback' => [
            'index.d.ts',
            ['package.json' => []],
            ['package.json', 'index.d.ts'],
        ];
        yield 'exports target mapped from js to the sibling d.ts' => [
            'dist/index.d.mts',
            ['package.json' => ['exports' => ['.' => ['import' => './dist/index.mjs']]]],
            ['package.json', 'dist/index.d.mts'],
        ];
        yield 'exports js target mapped to d.ts' => [
            'dist/index.d.ts',
            ['package.json' => ['exports' => './dist/index.js']],
            ['package.json', 'dist/index.d.ts'],
        ];
        yield 'd.cts excluded' => [
            null,
            ['package.json' => ['exports' => ['.' => ['require' => './dist/index.cjs', 'import' => './dist/index.cjs']]]],
            ['package.json', 'dist/index.d.cts'],
        ];
    }
}
