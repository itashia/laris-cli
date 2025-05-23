<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class DockerCommand extends Command
{
    protected static $defaultName = 'laris:docker';

    protected function configure(): void
    {
        $this->setName('laris:docker')->setDescription('Run Docker commands inside Laravel project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cwd = getcwd();

        if (!file_exists($cwd . '/artisan')) {
            $io->error('You are not in a Laravel project.');
            return Command::FAILURE;
        }

        $options = [
            'build'    => 'Build Docker images',
            'up'       => 'Start Docker containers',
            'down'     => 'Stop Docker containers',
            'ps'       => 'List running containers',
            'logs'     => 'Show logs of containers',
            'exec'     => 'Execute a command inside container',
            'quit'     => 'Exit',
        ];

        while (true) {
            $choice = $io->choice('Docker options', array_keys($options), 'ps');

            switch ($choice) {
                case 'build':
                    $this->runProcess(['docker-compose', 'build'], $cwd, $io);
                    break;
                case 'up':
                    $this->runProcess(['docker-compose', 'up', '-d'], $cwd, $io);
                    break;
                case 'down':
                    $this->runProcess(['docker-compose', 'down'], $cwd, $io);
                    break;
                case 'ps':
                    $this->runProcess(['docker-compose', 'ps'], $cwd, $io);
                    break;
                case 'logs':
                    $service = $io->ask('Enter service name for logs (empty for all)', '');
                    $cmd = ['docker-compose', 'logs', '--tail', '50', '-f'];
                    if ($service) {
                        $cmd[] = $service;
                    }
                    $this->runProcess($cmd, $cwd, $io);
                    break;
                case 'exec':
                    $service = $io->ask('Enter service name to exec into');
                    if (!$service) {
                        $io->error('Service name is required.');
                        break;
                    }
                    $command = $io->ask('Enter command to run inside container', '/bin/sh');
                    $this->runProcess(['docker-compose', 'exec', $service, $command], $cwd, $io);
                    break;
                case 'quit':
                    $io->success('Exiting Docker control.');
                    return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }

    private function runProcess(array $command, string $cwd, SymfonyStyle $io): void
    {
        $io->writeln("\n<comment>Running:</comment> " . implode(' ', $command));
        $process = new Process($command, $cwd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Command failed: ' . $process->getErrorOutput());
        }
    }
}
