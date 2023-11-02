<?php

namespace Sensiolabs\TypeScriptBundle\Command;

use Sensiolabs\TypeScriptBundle\TypeScriptBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch for changes and recompile');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->typeScriptBuilder->setOutput($io);

        $watch = $input->getOption('watch');
        $processes = [];
        foreach ($this->typeScriptBuilder->createAllBuildProcess($watch) as $process) {
            $processes[] = $process;
        }

        if (count($processes) === 0) {
            $io->success('No TypeScript files to compile');

            return self::SUCCESS;
        }

        do {
            foreach ($processes as $index => $process) {
                if ($process->isRunning()) {
                    continue;
                }
                if (!$process->isSuccessful()) {
                    $io->error('TypeScript build failed');

                    return self::FAILURE;
                }
                unset($processes[$index]);
            }
            usleep($watch ? 1000000 : 5000);
        } while (count($processes) > 0);
        return self::SUCCESS;
    }
}
