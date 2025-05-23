<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectSelectCommand extends Command
{
    protected static $defaultName = 'select';

    private array $history;

    public function __construct(array $history)
    {
        parent::__construct();
        $this->history = $history;
    }

    protected function configure(): void
    {
        $this->setName('select')->setDescription('Select a Laravel project to work with');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $i = 1;
        foreach ($this->history as $name => $path) {
            $output->writeln("[$i] $name => $path");
            $i++;
        }

        $output->write('Enter number: ');
        $selected = trim(fgets(STDIN));
        $index = (int)$selected - 1;

        $keys = array_keys($this->history);
        if (!isset($keys[$index])) {
            $output->writeln("<error>Invalid selection</error>");
            return Command::FAILURE;
        }

        chdir($this->history[$keys[$index]]);
        $output->writeln("<info>Switched to:</info> {$this->history[$keys[$index]]}");

        return Command::SUCCESS;
    }
}
