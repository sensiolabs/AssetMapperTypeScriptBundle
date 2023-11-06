<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Tools\WatchexecBinaryFactory;

class WatchexecBinaryFactoryTest extends TestCase
{
    private string $binaryDownloadDir = __DIR__.'/../fixtures/bin';
    private string $watchPath = __DIR__.'/../fixtures/assets/typescript';

    private function getBinaryFactory()
    {
        return new WatchexecBinaryFactory(
            $this->binaryDownloadDir,
        );
    }
    public function testGetBinaryFromPath()
    {
        // Test that the binary is found and the process is created with the correct arguments
        $binary = $this->getBinaryFactory()->getBinaryFromPath($this->binaryDownloadDir.'/swc-linux-x64-gnu');
        $process = $binary->createProcess($this->watchPath);
        $this->assertEquals('\''.$this->binaryDownloadDir.'/swc-linux-x64-gnu\' \'--exts\' \'ts\' \'-w\' \''.$this->watchPath.'\' \'echo "$WATCHEXEC_COMMON_PATH/$WATCHEXEC_WRITTEN_PATH"\'', $process->getCommandLine());

        // Test that an exception is thrown when the binary is not found
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The Watchexec binary could not be found at the provided path : "/wrong/path"');
        $this->getBinaryFactory()->getBinaryFromPath('/wrong/path');
    }

    public function testGetBinaryFromServerSpecs()
    {
        // Test that the binary is downloaded and the process is created with the correct arguments
        $binary = $this->getBinaryFactory()->getBinaryFromServerSpecs('Linux', 'x86_64', 'linux');
        $process = $binary->createProcess($this->watchPath);
        $this->assertEquals('\''.$this->binaryDownloadDir.'/watchexec-1.20.5-x86_64-unknown-linux-gnu/watchexec\' \'--exts\' \'ts\' \'-w\' \''.$this->watchPath.'\' \'echo "$WATCHEXEC_COMMON_PATH/$WATCHEXEC_WRITTEN_PATH"\'', $process->getCommandLine());

        // Test that an exception is thrown when the platform is not supported
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown platform or architecture (OS: undefined, Machine: x86_64).');
        $this->getBinaryFactory()->getBinaryFromServerSpecs('Undefined', 'x86_64', 'linux');
    }

    /**
     * @dataProvider provideServerSpecs
     */
    public function testGetBinaryNameFromServerSpecs($os, $machine, $kernel, $expectedBinaryName, $exception = null)
    {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $this->assertEquals($expectedBinaryName, WatchexecBinaryFactory::getBinaryNameFromServerSpecs($os, $machine, $kernel));
    }

    public function provideServerSpecs()
    {
        return [
            ['Darwin', 'x86_64', 'darwin', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-x86_64-apple-darwin'],
            ['Darwin', 'aarch64', 'darwin', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-aarch64-apple-darwin'],

            ['Linux', 'x86_64', 'linux', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-x86_64-unknown-linux-gnu'],
            ['Linux', 'arm64', 'linux', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-aarch64-unknown-linux-gnu'],
            ['Linux', 'aarch64', 'linux', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-aarch64-unknown-linux-gnu'],

            ['Linux', 'x86_64', 'linux-musl', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-x86_64-unknown-linux-musl'],
            ['Linux', 'arm64', 'linux-musl', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-aarch64-unknown-linux-musl'],
            ['Linux', 'aarch64', 'linux-musl', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-aarch64-unknown-linux-musl'],

            ['Windows', 'x86_64', 'windows', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-x86_64-pc-windows-msvc'],

            ['Undefined', 'x86_64', 'darwin', null, \Exception::class],
            ['Darwin', 'undefined', 'darwin', null, \Exception::class],
            ['Linux', 'x86_64', 'undefined', 'watchexec-'.WatchexecBinaryFactory::VERSION.'-x86_64-unknown-linux-gnu'],
        ];
    }
}
