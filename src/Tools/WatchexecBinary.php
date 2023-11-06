<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

use Symfony\Component\Process\Process;

class WatchexecBinary
{
    public function __construct(
        private readonly string $executablePath,
    ) {
        if (!file_exists($this->executablePath)) {
            throw new \Exception(sprintf('The Watchexec binary could not be found at the provided path : "%s"', $this->executablePath));
        }
    }

    public function createProcess(string $watchPath): Process
    {
        $args = ['--exts', 'ts', '-w', $watchPath, 'echo "$WATCHEXEC_COMMON_PATH/$WATCHEXEC_WRITTEN_PATH"'];
        array_unshift($args, $this->executablePath);

        return new Process($args);
    }
}
