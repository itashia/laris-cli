<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HookCommand extends Command
{
    protected static $defaultName = 'laris:hook';

    protected function configure(): void
    {
        $this
            ->setName('laris:hook')->setDescription('Manage git hooks in your project')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list, add, remove, show')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Hook name (e.g. pre-commit, post-merge)')
            ->addOption('script', null, InputOption::VALUE_OPTIONAL, 'Script or command to add to the hook');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cwd = getcwd();
        $gitHooksDir = $cwd . '/.git/hooks';

        if (!is_dir($gitHooksDir)) {
            $io->error('This directory does not seem to be a git repository (missing .git/hooks folder).');
            return Command::FAILURE;
        }

        $action = $input->getArgument('action');
        $name = $input->getOption('name');
        $script = $input->getOption('script');

        switch ($action) {
            case 'list':
                $hooks = array_filter(scandir($gitHooksDir), function($file) use ($gitHooksDir) {
                    return is_file($gitHooksDir . '/' . $file) && is_executable($gitHooksDir . '/' . $file);
                });
                if (empty($hooks)) {
                    $io->writeln('No git hooks found.');
                } else {
                    $io->title('Git hooks in .git/hooks');
                    foreach ($hooks as $hook) {
                        $io->writeln("- $hook");
                    }
                }
                break;

            case 'add':
                if (!$name) {
                    $io->error('Please provide the --name option for the hook name (e.g. pre-commit).');
                    return Command::FAILURE;
                }
                if (!$script) {
                    $io->error('Please provide the --script option with the command or script to add.');
                    return Command::FAILURE;
                }
                $hookPath = $gitHooksDir . '/' . $name;
                $content = "#!/bin/sh\n\n" . $script . "\n";
                if (file_exists($hookPath)) {
                    $io->warning("Hook '$name' already exists. It will be overwritten.");
                }
                file_put_contents($hookPath, $content);
                chmod($hookPath, 0755);
                $io->success("Hook '$name' created with provided script.");
                break;

            case 'remove':
                if (!$name) {
                    $io->error('Please provide the --name option for the hook name to remove.');
                    return Command::FAILURE;
                }
                $hookPath = $gitHooksDir . '/' . $name;
                if (!file_exists($hookPath)) {
                    $io->error("Hook '$name' does not exist.");
                    return Command::FAILURE;
                }
                unlink($hookPath);
                $io->success("Hook '$name' removed.");
                break;

            case 'show':
                if (!$name) {
                    $io->error('Please provide the --name option for the hook name to show.');
                    return Command::FAILURE;
                }
                $hookPath = $gitHooksDir . '/' . $name;
                if (!file_exists($hookPath)) {
                    $io->error("Hook '$name' does not exist.");
                    return Command::FAILURE;
                }
                $content = file_get_contents($hookPath);
                $io->title("Contents of hook '$name':");
                $io->writeln($content);
                break;

            default:
                $io->error("Unknown action '$action'. Valid actions are: list, add, remove, show.");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
