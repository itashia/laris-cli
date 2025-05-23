<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ComposerCommand extends Command
{
    /**
     * The default name of the command.
     *
     * @var string
     */
    protected static $defaultName = 'laris:composer';

    /**
     * Configure the command name and description.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('laris:composer')
            ->setDescription('Run composer commands inside Laravel project');
    }

    /**
     * Execute the composer command interface.
     *
     * Presents a menu for various composer actions such as update,
     * install, require, remove, and dump-autoload, then runs the selected command.
     *
     * @param InputInterface  $input  The input interface instance.
     * @param OutputInterface $output The output interface instance.
     * @return int Exit code indicating success or failure.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cwd = getcwd();

        // Check if current directory is a Laravel project by looking for artisan file
        if (!file_exists($cwd . '/artisan')) {
            $io->error('You are not in a Laravel project.');
            return Command::FAILURE;
        }

        // Define available composer commands with descriptions
        $options = [
            'update'        => 'Run composer update',
            'dump-autoload' => 'Run composer dump-autoload',
            'install'       => 'Run composer install',
            'require'       => 'Add a composer package',
            'remove'        => 'Remove a composer package',
            'quit'          => 'Exit',
        ];

        // Loop to allow multiple composer commands until user chooses to quit
        while (true) {
            $choice = $io->choice('Composer options', array_keys($options), 'update');

            switch ($choice) {
                case 'update':
                    $this->runProcess(['composer', 'update'], $cwd, $io);
                    break;

                case 'dump-autoload':
                    $this->runProcess(['composer', 'dump-autoload'], $cwd, $io);
                    break;

                case 'install':
                    $this->runProcess(['composer', 'install'], $cwd, $io);
                    break;

                case 'require':
                    $package = $io->ask('Enter package name to require (e.g. monolog/monolog)');
                    if ($package) {
                        $this->runProcess(['composer', 'require', $package], $cwd, $io);
                    } else {
                        $io->warning('Package name cannot be empty.');
                    }
                    break;

                case 'remove':
                    $package = $io->ask('Enter package name to remove');
                    if ($package) {
                        $this->runProcess(['composer', 'remove', $package], $cwd, $io);
                    } else {
                        $io->warning('Package name cannot be empty.');
                    }
                    break;

                case 'quit':
                    $io->success('Exiting Composer control.');
                    return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Run a system process for the given command inside the project directory.
     *
     * Outputs command progress and errors directly to console.
     *
     * @param array        $command The command and its arguments to run.
     * @param string       $cwd     The working directory to run the command in.
     * @param SymfonyStyle $io      SymfonyStyle instance for console output.
     * @return void
     */
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
