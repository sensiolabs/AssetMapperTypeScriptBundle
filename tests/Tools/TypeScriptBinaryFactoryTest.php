<?php

namespace Sensiolabs\TypeScriptBundle\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Sensiolabs\TypeScriptBundle\Tools\TypeScriptBinaryFactory;

class TypeScriptBinaryFactoryTest extends TestCase
{
    private string $binaryDownloadDir = __DIR__.'/../fixtures/bin';

    private function getBinaryFactory(): TypeScriptBinaryFactory
    {
        return new TypeScriptBinaryFactory(
            $this->binaryDownloadDir,
            'v1.3.92'
        );
    }

    public function testGetBinaryFromPath(): void
    {
        // Test that the binary is found and the process is created with the correct arguments
        $binary = $this->getBinaryFactory()->getBinaryFromPath($this->binaryDownloadDir.'/swc-linux-x64-gnu');
        $process = $binary->createProcess(['arg1', 'arg2']);
        $this->assertEquals('\''.$this->binaryDownloadDir.'/swc-linux-x64-gnu\' \'arg1\' \'arg2\'', $process->getCommandLine());

        // Test that an exception is thrown when the binary is not found
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The Typescript binary could not be found at the provided path : "/wrong/path"');
        $this->getBinaryFactory()->getBinaryFromPath('/wrong/path');
    }

    public function testGetBinaryFromServerSpecs(): void
    {
        // Test that the binary is downloaded and the process is created with the correct arguments
        $binary = $this->getBinaryFactory()->getBinaryFromServerSpecs('Linux', 'x86_64', 'linux');
        $process = $binary->createProcess(['arg1', 'arg2']);
        $this->assertEquals('\''.$this->binaryDownloadDir.'/swc-linux-x64-gnu\' \'arg1\' \'arg2\'', $process->getCommandLine());

        // Test that an exception is thrown when the platform is not supported
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown platform or architecture (OS: undefined, Machine: x86_64).');
        $this->getBinaryFactory()->getBinaryFromServerSpecs('Undefined', 'x86_64', 'linux');
    }

    /**
     * @dataProvider provideServerSpecs
     */
    public function testGetBinaryNameFromServerSpecs(string $os, string $machine, string $kernel, ?string $expectedBinaryName, ?string $exception = null): void
    {
        if (null !== $exception && is_subclass_of($exception, \Throwable::class)) {
            $this->expectException($exception);
        }

        $this->assertEquals($expectedBinaryName, TypeScriptBinaryFactory::getBinaryNameFromServerSpecs($os, $machine, $kernel));
    }

    /**
     * @return list<array<string|null>>
     */
    public static function provideServerSpecs(): array
    {
        return [
            ['Darwin', 'x86_64', 'darwin', 'swc-darwin-x64'],
            ['Darwin', 'arm64', 'darwin', 'swc-darwin-arm64'],

            ['Linux', 'x86_64', 'linux', 'swc-linux-x64-gnu'],
            ['Linux', 'arm64', 'linux', 'swc-linux-arm64-gnu'],

            ['Linux', 'x86_64', 'linux-musl', 'swc-linux-x64-musl'],
            ['Linux', 'arm64', 'linux-musl', 'swc-linux-arm64-musl'],

            ['Windows', 'x86_64', 'windows', 'swc-win32-x64-msvc.exe'],
            ['Windows', 'amd64', 'windows', 'swc-win32-x64-msvc.exe'],
            ['Windows', 'arm64', 'windows', 'swc-win32-arm64-msvc.exe'],
            ['Windows', 'i586', 'windows', 'swc-win32-ia32-msvc.exe'],

            ['Undefined', 'x86_64', 'darwin', null, \Exception::class],
            ['Darwin', 'undefined', 'darwin', null, \Exception::class],
            ['Linux', 'x86_64', 'undefined', 'swc-linux-x64-gnu'],
        ];
    }
}
