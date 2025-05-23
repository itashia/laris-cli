<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class NpmCommand extends Command
{
    protected static $defaultName = 'laris:npm';

    protected function configure(): void
    {
        $this->setName('laris:npm')->setDescription('Manage npm/yarn/pnpm commands')
            ->addArgument('action', InputArgument::REQUIRED, 'Action (install, run, build, update, cache-clean, scripts, version, npx)')
            ->addArgument('packageOrScripts', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Package name(s) or script(s) (comma separated for run)')
            ->addOption('save-dev', null, InputOption::VALUE_NONE, 'Use --save-dev when installing package')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Use --save when installing package')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output JSON formatted result (for run scripts)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force option for cache clean');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cwd = getcwd();

        if (!file_exists($cwd . '/package.json')) {
            $io->error('package.json not found in the current directory.');
            return Command::FAILURE;
        }

        $action = strtolower($input->getArgument('action'));
        $args = $input->getArgument('packageOrScripts') ?? [];

        $packageManager = $this->detectPackageManager($cwd);

        switch ($action) {
            case 'install':
                return $this->handleInstall($args, $input, $io, $cwd, $packageManager);

            case 'update':
                return $this->handleUpdate($args, $io, $cwd, $packageManager);

            case 'run':
            case 'build':  // treat build as run build
                return $this->handleRun($action, $args, $input, $io, $cwd, $packageManager);

            case 'cache-clean':
                return $this->handleCacheClean($input, $io, $cwd, $packageManager);

            case 'scripts':
                return $this->handleScripts($io, $cwd);

            case 'version':
                return $this->handleVersion($io, $packageManager);

            case 'npx':
                return $this->handleNpx($args, $io, $cwd);

            default:
                $io->error("Unknown action: $action");
                return Command::FAILURE;
        }
    }

    private function detectPackageManager(string $cwd): string
    {
        if (file_exists($cwd . '/yarn.lock')) {
            return 'yarn';
        } elseif (file_exists($cwd . '/pnpm-lock.yaml')) {
            return 'pnpm';
        }
        return 'npm';
    }

    private function handleInstall(array $args, InputInterface $input, SymfonyStyle $io, string $cwd, string $pm): int
    {
        $command = [$pm];

        if ($pm === 'yarn') {
            // yarn add package ...
            $command[] = 'add';
        } elseif ($pm === 'pnpm') {
            $command[] = 'add';
        } else {
            // npm install ...
            $command[] = 'install';
        }

        if (!empty($args)) {
            foreach ($args as $package) {
                $command[] = $package;
            }
        }

        if ($input->getOption('save-dev')) {
            if ($pm === 'npm' || $pm === 'pnpm') {
                $command[] = '--save-dev';
            } elseif ($pm === 'yarn') {
                // yarn add --dev package
                $command[] = '--dev';
            }
        } elseif ($input->getOption('save')) {
            if ($pm === 'npm' || $pm === 'pnpm') {
                $command[] = '--save';
            }
            // yarn add by default saves to dependencies
        }

        return $this->runProcess($command, $cwd, $io);
    }

    private function handleUpdate(array $args, SymfonyStyle $io, string $cwd, string $pm): int
    {
        $command = [$pm];

        if ($pm === 'yarn') {
            $command[] = 'upgrade';
            if (!empty($args)) {
                foreach ($args as $package) {
                    $command[] = $package;
                }
            }
        } elseif ($pm === 'pnpm') {
            $command[] = 'update';
            if (!empty($args)) {
                foreach ($args as $package) {
                    $command[] = $package;
                }
            }
        } else {
            // npm
            $command[] = 'update';
            if (!empty($args)) {
                foreach ($args as $package) {
                    $command[] = $package;
                }
            }
        }

        return $this->runProcess($command, $cwd, $io);
    }

    private function handleRun(string $action, array $args, InputInterface $input, SymfonyStyle $io, string $cwd, string $pm): int
    {
        $scriptsToRun = [];

        if ($action === 'build') {
            // treat build as run build
            $scriptsToRun[] = 'build';
        } else {
            // run script(s)
            if (empty($args)) {
                $io->error("Please specify script name(s) to run, comma separated if multiple.");
                return Command::FAILURE;
            }
            // split comma separated if one string contains comma
            $scriptsToRun = [];
            foreach ($args as $arg) {
                $split = array_map('trim', explode(',', $arg));
                $scriptsToRun = array_merge($scriptsToRun, $split);
            }
        }

        foreach ($scriptsToRun as $script) {
            $command = [$pm];
            if ($pm === 'yarn') {
                $command[] = 'run';
                $command[] = $script;
            } elseif ($pm === 'pnpm') {
                $command[] = 'run';
                $command[] = $script;
            } else {
                $command[] = 'run';
                $command[] = $script;
            }

            if ($input->getOption('json')) {
                // for npm, we can add --json for some commands, but run scripts normally don't have that option
                // so just run normally and maybe later parse output if needed
            }

            $ret = $this->runProcess($command, $cwd, $io);
            if ($ret !== Command::SUCCESS) {
                return $ret;
            }
        }

        return Command::SUCCESS;
    }

    private function handleCacheClean(InputInterface $input, SymfonyStyle $io, string $cwd, string $pm): int
    {
        $command = [$pm, 'cache', 'clean'];

        if ($input->getOption('force')) {
            $command[] = '--force';
        }

        return $this->runProcess($command, $cwd, $io);
    }

    private function handleScripts(SymfonyStyle $io, string $cwd): int
    {
        $packageJson = json_decode(file_get_contents($cwd . '/package.json'), true);

        if (empty($packageJson['scripts'])) {
            $io->warning('No scripts found in package.json.');
            return Command::SUCCESS;
        }

        $io->section('Available scripts in package.json:');
        foreach ($packageJson['scripts'] as $name => $command) {
            $io->writeln("  <info>{$name}</info>: {$command}");
        }

        return Command::SUCCESS;
    }

    private function handleVersion(SymfonyStyle $io, string $pm): int
    {
        $nodeVersion = null;
        $npmVersion = null;

        $nodeProcess = new Process(['node', '--version']);
        $nodeProcess->run();
        if ($nodeProcess->isSuccessful()) {
            $nodeVersion = trim($nodeProcess->getOutput());
        }

        $pmProcess = new Process([$pm, '--version']);
        $pmProcess->run();
        if ($pmProcess->isSuccessful()) {
            $npmVersion = trim($pmProcess->getOutput());
        }

        $io->writeln("Node.js version: " . ($nodeVersion ?? 'unknown'));
        $io->writeln(ucfirst($pm) . " version: " . ($npmVersion ?? 'unknown'));

        return Command::SUCCESS;
    }

    private function handleNpx(array $args, SymfonyStyle $io, string $cwd): int
    {
        if (empty($args)) {
            $io->error("Please specify command to run with npx.");
            return Command::FAILURE;
        }

        $command = array_merge(['npx'], $args);

        return $this->runProcess($command, $cwd, $io);
    }

    private function runProcess(array $command, string $cwd, SymfonyStyle $io): int
    {
        $io->writeln("\n<comment>Running:</comment> " . implode(' ', $command));
        $process = new Process($command, $cwd);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Command failed: ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
