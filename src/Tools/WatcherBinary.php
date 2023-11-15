<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

use Symfony\Component\Process\Process;

class WatcherBinary
{
    public function __construct(
        private string $executablePath,
    ) {
    }

    /**
     * @param array<string> $extensions
     */
    public function startWatch(string $watchPath, callable $callback, array $extensions = []): Process
    {
        $process = new Process([$this->executablePath, $watchPath]);

        $process->start(function ($type, $buffer) use ($callback, $extensions) {
            if (Process::ERR === $type) {
                throw new \Exception($buffer);
            } else {
                $lines = explode("\n", $buffer);
                $changedFiles = [];
                foreach ($lines as $line) {
                    try {
                        $entry = json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
                        if ($extensions && !\in_array(pathinfo($entry['name'], \PATHINFO_EXTENSION), $extensions)) {
                            continue;
                        }
                        $changedFiles[$entry['name']] = $entry['operation'];
                    } catch (\JsonException) {
                        continue;
                    }
                }
                foreach ($changedFiles as $file => $operation) {
                    $callback($file, $operation);
                }
            }
        });

        return $process;
    }
}
