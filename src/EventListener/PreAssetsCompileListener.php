<?php

namespace Sensiolabs\TypeScriptBundle\EventListener;

use Sensiolabs\TypeScriptBundle\TypeScriptBuilder;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreAssetsCompileListener
{
    public function __construct(private readonly TypeScriptBuilder $typeScriptBuilder)
    {
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        $output = new SymfonyStyle(new ArrayInput([]), $event->getOutput());
        $this->typeScriptBuilder
            ->setOutput($output);
        foreach ($this->typeScriptBuilder->createAllBuildProcess() as $process) {
            $process->wait(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $output->error('TypeScript build failed');
                throw new \Exception('TypeScript build failed');
            }
        }
    }
}
