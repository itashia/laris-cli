<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class GitCommand extends Command
{
    protected static $defaultName = 'laris:git';

    protected function configure(): void
    {
        $this->setName('laris:git')->setDescription('Run git commands inside laravel project');
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
            'init'   => 'Initialize git repository',
            'status' => 'Show git status',
            'commit' => 'Commit changes',
            'push'   => 'Push to origin',
            'remote' => 'Add remote URL',
            'pull'   => 'Pull from origin',
            'log'    => 'Show git log',
            'quit'   => 'Exit',
        ];

        while (true) {
            $choice = $io->choice('Git options', array_keys($options), 'status');

            switch ($choice) {
                case 'init':
                    $this->runProcess(['git', 'init'], $cwd, $io);
                    break;
                case 'status':
                    $this->runProcess(['git', 'status'], $cwd, $io);
                    break;
                case 'commit':
                    $message = $io->ask('Enter commit message', 'Update project');
                    $this->runProcess(['git', 'add', '.'], $cwd, $io);
                    $this->runProcess(['git', 'commit', '-m', $message], $cwd, $io);
                    break;
                case 'push':
                    $this->runProcess(['git', 'push'], $cwd, $io);
                    break;
                case 'remote':
                    $url = $io->ask('Enter remote URL');
                    $this->runProcess(['git', 'remote', 'add', 'origin', $url], $cwd, $io);
                    break;
                case 'pull':
                    $this->runProcess(['git', 'pull'], $cwd, $io);
                    break;
                case 'log':
                    $this->runProcess(['git', 'log', '--oneline', '--graph', '--all'], $cwd, $io);
                    break;
                case 'quit':
                    $io->success('Exiting Git control.');
                    return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }

    private function runProcess(array $command, string $cwd, SymfonyStyle $io): void
    {
        $io->writeln("\n<comment>Running:</comment> " . implode(' ', $command));
        $process = new Process($command, $cwd);
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Command failed: ' . $process->getErrorOutput());
        }
    }
}
