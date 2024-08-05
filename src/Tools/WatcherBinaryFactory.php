<?php

namespace Sensiolabs\TypeScriptBundle\Tools;

class WatcherBinaryFactory
{
    public function getBinaryFromServerSpecs(string $os): WatcherBinary
    {
        $binaryName = self::getBinaryNameFromServerSpecs($os);
        $binaryPath = __DIR__.'/watcher/'.$binaryName;
        if (!file_exists($binaryPath)) {
            throw new \Exception(\sprintf('The watcher binary could not be found at the provided path : "%s"', $binaryPath));
        }

        return new WatcherBinary($binaryPath);
    }

    public static function getBinaryNameFromServerSpecs(string $os): string
    {
        $os = strtolower($os);
        if (str_contains($os, 'darwin')) {
            return 'watcher-darwin';
        }
        if (str_contains($os, 'linux')) {
            return 'watcher-linux';
        }
        if (str_contains($os, 'win')) {
            return 'watcher-windows.exe';
        }

        throw new \Exception(\sprintf('Unknown platform or architecture (OS: %s).', $os));
    }
}
