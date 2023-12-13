<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinaryFactory;

class WatcherBinaryFactoryTest extends TestCase
{
    private string $watchPath = __DIR__.'/../fixtures/assets/typescript';
    private string $binaryDir = __DIR__.'/../../src/Tools/watcher';

    public function testGetBinaryFromServerSpecs()
    {
        // Test that the binary exists and the process is created with the correct arguments
        $binary = (new WatcherBinaryFactory())->getBinaryFromServerSpecs('Linux');
        $process = $binary->startWatch($this->watchPath, fn ($path, $operation) => '');
        $this->assertEquals('\''.realpath($this->binaryDir).'/watcher-linux\' \''.$this->watchPath.'/...\'', $process->getCommandLine());

        // Test that an exception is thrown when the platform is not supported
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown platform or architecture (OS: undefined).');
        (new WatcherBinaryFactory())->getBinaryFromServerSpecs('Undefined');
    }

    /**
     * @dataProvider provideServerSpecs
     */
    public function testGetBinaryNameFromServerSpecs($os, $expectedBinaryName, $exception = null)
    {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $this->assertEquals($expectedBinaryName, WatcherBinaryFactory::getBinaryNameFromServerSpecs($os));
    }

    public function provideServerSpecs()
    {
        return [
            ['Darwin', 'watcher-darwin'],
            ['Linux', 'watcher-linux'],
            ['Windows', 'watcher-windows.exe'],
            ['Undefined', null, \Exception::class],
        ];
    }
}
