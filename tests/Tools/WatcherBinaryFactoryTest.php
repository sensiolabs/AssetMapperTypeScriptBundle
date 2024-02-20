<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Tools\WatcherBinaryFactory;

class WatcherBinaryFactoryTest extends TestCase
{
    private string $watchPath = __DIR__.'/../fixtures/assets/typescript';
    private string $binaryDir = __DIR__.'/../../src/Tools/watcher';

    public function testGetBinaryFromServerSpecs(): void
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
    public function testGetBinaryNameFromServerSpecs(string $os, ?string $expectedBinaryName, ?string $exception = null): void
    {
        if (null !== $exception && is_subclass_of($exception, \Throwable::class)) {
            $this->expectException($exception);
        }

        $this->assertEquals($expectedBinaryName, WatcherBinaryFactory::getBinaryNameFromServerSpecs($os));
    }

    /**
     * @return list<array<string|null>>
     */
    public static function provideServerSpecs(): array
    {
        return [
            ['Darwin', 'watcher-darwin'],
            ['Linux', 'watcher-linux'],
            ['Windows', 'watcher-windows.exe'],
            ['Undefined', null, \Exception::class],
        ];
    }
}
