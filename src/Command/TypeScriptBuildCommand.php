<?php

namespace Sensiolabs\TypeScriptBundle\Command;

use Sensiolabs\TypeScriptBundle\TypeScriptBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'typescript:build',
    description: 'Compile TypeScript files to JavaScript'
)]
class TypeScriptBuildCommand extends Command
{
    public function __construct(
        private TypeScriptBuilder $typeScriptBuilder
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->typeScriptBuilder->setOutput($io);

        foreach ($this->typeScriptBuilder->runBuild() as $process) {
            $process->wait(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $io->error('TypeScript build failed');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
