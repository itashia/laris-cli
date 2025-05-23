<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected static $defaultName = 'laris:deploy';

    protected function configure(): void
    {
        $this->setName('laris:deploy')->setDescription('Prepare the project for deployment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectDir = getcwd();

        $io->title('Starting deployment preparation');

        // Run composer install with no dev dependencies and optimized autoloader if composer.json exists
        if (file_exists($projectDir . '/composer.json')) {
            $io->writeln('Running composer install --no-dev --optimize-autoloader ...');
            $composerProcess = new Process(['composer', 'install', '--no-dev', '--optimize-autoloader'], $projectDir);
            $composerProcess->run(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });
            if (!$composerProcess->isSuccessful()) {
                $io->error('Composer install failed');
                return Command::FAILURE;
            }
            $io->success('Composer dependencies installed');
        } else {
            $io->warning('composer.json not found, skipping composer install');
        }

        // Run npm install and production build if package.json exists
        if (file_exists($projectDir . '/package.json')) {
            $io->writeln('Running npm install ...');
            $npmInstall = new Process(['npm', 'install'], $projectDir);
            $npmInstall->run(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });
            if (!$npmInstall->isSuccessful()) {
                $io->error('npm install failed');
                return Command::FAILURE;
            }
            $io->success('npm packages installed');

            $io->writeln('Running npm run production ...');
            $npmBuild = new Process(['npm', 'run', 'production'], $projectDir);
            $npmBuild->run(function ($type, $buffer) use ($io) {
                $io->write($buffer);
            });
            if (!$npmBuild->isSuccessful()) {
                $io->error('npm run production failed');
                return Command::FAILURE;
            }
            $io->success('npm build completed');
        } else {
            $io->warning('package.json not found, skipping npm install and build');
        }

        // Clear and cache Laravel config, routes and views if artisan file exists
        if (file_exists($projectDir . '/artisan')) {
            $io->writeln('Clearing and caching Laravel config & routes ...');
            $commands = [
                ['php', 'artisan', 'config:cache'],
                ['php', 'artisan', 'route:cache'],
                ['php', 'artisan', 'view:cache'],
            ];
            foreach ($commands as $cmd) {
                $process = new Process($cmd, $projectDir);
                $process->run(function ($type, $buffer) use ($io) {
                    $io->write($buffer);
                });
                if (!$process->isSuccessful()) {
                    $io->error("Command " . implode(' ', $cmd) . " failed");
                    return Command::FAILURE;
                }
            }
            $io->success('Laravel caches refreshed');
        } else {
            $io->warning('artisan file not found, skipping Laravel cache commands');
        }

        $io->success('Deployment preparation completed successfully.');

        return Command::SUCCESS;
    }
}
