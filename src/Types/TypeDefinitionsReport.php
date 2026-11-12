<?php

namespace Sensiolabs\TypeScriptBundle\Types;

class TypeDefinitionsReport
{
    /**
     * @param array<string, array{package: string, path: string}|null> $imports declaration file of each import name, null when none was found
     */
    public function __construct(
        public readonly array $imports,
        public readonly string $tsConfigPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getMissingImports(): array
    {
        return array_keys(array_filter($this->imports, static fn (?array $import) => null === $import));
    }
}
