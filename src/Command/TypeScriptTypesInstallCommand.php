<?php

namespace Sensiolabs\TypeScriptBundle\Command;

use Sensiolabs\TypeScriptBundle\Types\TypeDefinitionsInstaller;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'typescript:types:install',
    description: 'Download the TypeScript declaration files of the importmap packages'
)]
class TypeScriptTypesInstallCommand extends Command
{
    public function __construct(
        private ImportMapConfigReader $importMapConfigReader,
        private TypeDefinitionsInstaller $typeDefinitionsInstaller,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = $this->typeDefinitionsInstaller->install($this->importMapConfigReader->getEntries());

        if (!$report->imports) {
            $io->success('No remote JavaScript package found in the importmap.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($report->imports as $importName => $import) {
            $rows[] = [$importName, $import['package'] ?? '<comment>not found</comment>'];
        }
        $io->table(['Import', 'Declaration files'], $rows);

        $tsConfigPath = Path::makeRelative($report->tsConfigPath, $this->projectDir);
        $io->success(\sprintf('Declaration files installed. Extend "%s" from your tsconfig.json to use them.', $tsConfigPath));
        $io->writeln(\sprintf('    "extends": "./%s"', $tsConfigPath));

        if ($missing = $report->getMissingImports()) {
            $io->note(\sprintf('No declaration files found for "%s". Declare them in a local .d.ts file, e.g. declare module \'%s\';', implode('", "', $missing), $missing[0]));
        }

        return self::SUCCESS;
    }
}
