<?php

namespace Sensiolabs\TypeScriptBundle\Types;

/**
 * Finds the declaration file TypeScript would load for an import of an npm package,
 * from its package.json files and the list of its files.
 */
class PackageTypesResolver
{
    // Export conditions a browser-targeted TypeScript resolution would pick, "types" being always tried first
    private const EXPORT_CONDITIONS = ['types', 'import', 'module', 'browser', 'default'];

    /**
     * Finds the declaration file TypeScript would load for "import 'package/subpath'".
     *
     * @param array<string, array<string, mixed>> $manifests decoded package.json files of the package, by path: "package.json" is the root one
     * @param array<string, true>                 $files     files of the package, by path
     * @param string                              $subpath   "." for the package root, "./sub" for a subpath
     */
    public static function resolve(array $manifests, array $files, string $subpath): ?string
    {
        $manifest = $manifests['package.json'] ?? [];
        $candidates = [];
        if (isset($manifest['exports']) && null !== $target = self::selectExportTarget($manifest['exports'], $subpath)) {
            $candidates = self::resolveConditions($target);
        }
        if ('.' === $subpath) {
            // Also tried after exports: some packages declare their types only at the top level
            array_push($candidates, $manifest['types'] ?? $manifest['typings'] ?? null, $manifest['module'] ?? null, $manifest['main'] ?? null, 'index.js');
        } elseif (!$candidates) {
            $candidates = [$subpath];
            // A subpath may have its own package.json, as exports did not exist yet
            $nested = $manifests[substr($subpath, 2).'/package.json'] ?? [];
            foreach (['types', 'typings', 'module', 'main'] as $field) {
                if (\is_string($nested[$field] ?? null)) {
                    $candidates[] = substr($subpath, 2).'/'.$nested[$field];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (!\is_string($candidate)) {
                continue;
            }
            foreach (self::getDeclarationCandidates(self::normalizePath($candidate)) as $declaration) {
                if (isset($files[$declaration])) {
                    return $declaration;
                }
            }
        }

        return null;
    }

    public static function isDeclarationFile(string $path): bool
    {
        // .d.cts files describe CommonJS builds, which a browser ESM import never loads
        return (bool) preg_match('/\.d\.m?ts$/', $path);
    }

    private static function selectExportTarget(mixed $exports, string $subpath): mixed
    {
        if (!\is_array($exports) || array_is_list($exports) || !str_starts_with((string) array_key_first($exports), '.')) {
            // Either a single target or a conditions object, both describing the root export only
            return '.' === $subpath ? $exports : null;
        }

        if (isset($exports[$subpath])) {
            return $exports[$subpath];
        }

        $patterns = array_filter($exports, static fn (string $pattern) => str_contains($pattern, '*'), \ARRAY_FILTER_USE_KEY);
        // Like Node, the pattern with the longest prefix wins
        uksort($patterns, static fn (string $a, string $b) => strpos($b, '*') <=> strpos($a, '*'));
        foreach ($patterns as $pattern => $target) {
            [$prefix, $suffix] = explode('*', $pattern, 2);
            if (str_starts_with($subpath, $prefix) && str_ends_with($subpath, $suffix) && \strlen($subpath) >= \strlen($prefix.$suffix)) {
                $match = substr($subpath, \strlen($prefix), \strlen($subpath) - \strlen($prefix) - \strlen($suffix));

                return self::replaceWildcard($target, $match);
            }
        }

        return null;
    }

    private static function replaceWildcard(mixed $target, string $match): mixed
    {
        if (\is_string($target)) {
            return str_replace('*', $match, $target);
        }

        return \is_array($target) ? array_map(static fn ($value) => self::replaceWildcard($value, $match), $target) : $target;
    }

    /**
     * @return list<string>
     */
    private static function resolveConditions(mixed $target): array
    {
        if (\is_string($target)) {
            return [$target];
        }
        if (!\is_array($target)) {
            return [];
        }
        if (array_is_list($target)) {
            return array_merge(...array_map(self::resolveConditions(...), $target));
        }

        $candidates = isset($target['types']) ? self::resolveConditions($target['types']) : [];
        foreach ($target as $condition => $value) {
            if ('types' !== $condition && \in_array($condition, self::EXPORT_CONDITIONS, true)) {
                $candidates = array_merge($candidates, self::resolveConditions($value));
            }
        }

        return $candidates;
    }

    /**
     * @return list<string>
     */
    private static function getDeclarationCandidates(string $path): array
    {
        if (self::isDeclarationFile($path)) {
            return [$path];
        }

        if (preg_match('/^(.+)\.([mc]?)[jt]sx?$/', $path, $matches)) {
            return 'c' === $matches[2] ? [] : [$matches[1].'.d.'.$matches[2].'ts'];
        }

        return [$path.'.d.ts', $path.'/index.d.ts'];
    }

    private static function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');

        return str_starts_with($path, './') ? substr($path, 2) : $path;
    }
}
