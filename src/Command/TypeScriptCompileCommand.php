<?php

namespace Sensiolabs\TypescriptBundle\Command;

use Sensiolabs\TypescriptBundle\TypeScriptBinary;
use Sensiolabs\TypescriptBundle\TypeScriptBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'typescript:build',
    description: 'Compile TypeScript files to JavaScript'
)]
class TypeScriptCompileCommand extends Command
{
    public function __construct(
        private TypeScriptBuilder $typeScriptCompiler
    ) {
        parent::__construct();
    }

    public function configure(): void
    {

    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $error = false;
        $this->typeScriptCompiler->setOutput($io);

        foreach ($this->typeScriptCompiler->runBuild(false) as $process) {
            $process->wait(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $io->error('Sass build failed');
                $error = true;
            }
        }
        return $error ? self::FAILURE : self::SUCCESS;
    }
}
