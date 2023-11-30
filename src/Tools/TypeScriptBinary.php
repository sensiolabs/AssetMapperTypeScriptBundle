<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

use Symfony\Component\Process\Process;

class TypeScriptBinary
{
    public function __construct(
        private readonly string $pathToExecutable,
    ) {
        if (!file_exists($this->pathToExecutable)) {
            throw new \Exception(sprintf('The Typescript binary could not be found at the provided path : "%s"', $this->pathToExecutable));
        }
    }

    /**
     * @param non-empty-list<string> $args
     */
    public function createProcess(array $args): Process
    {
        array_unshift($args, $this->pathToExecutable);

        return new Process($args);
    }
}
