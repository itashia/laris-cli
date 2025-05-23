<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NewCommandLaris extends Command
{
    protected static $defaultName = 'laris:new';

    private array $protectedCommands = [
        'laris:git',
        'laris:docker',
        'laris:composer',
        'laris:db',
        'laris:npm',
        'laris:config',
        'laris:docs',
        'laris:hook',
        'laris:new',
    ];

    private string $commandsDir;

    public function __construct()
    {
        parent::__construct();
        $this->commandsDir = __DIR__ . '/Laris';
        if (!is_dir($this->commandsDir)) {
            mkdir($this->commandsDir, 0755, true);
        }
    }

    protected function configure(): void
    {
        $this->setName('laris:new')->setDescription('Create or remove a custom laris command interactively');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Manage laris commands');

        $action = $io->choice('What do you want to do?', ['create', 'delete'], 'create');

        $commandName = $io->ask('Enter command name (e.g. serve, migrate)', null, function ($value) {
            $value = trim($value);
            if (!$value) {
                throw new \RuntimeException('Command name cannot be empty.');
            }
            if (!preg_match('/^[a-z0-9:_-]+$/i', $value)) {
                throw new \RuntimeException('Command name must be alphanumeric, colon, underscore or dash only.');
            }
            if (stripos($value, 'laris:') === 0) {
                return strtolower($value);
            }
            return 'laris:' . strtolower($value);
        });

        $className = $this->commandNameToClassName($commandName);
        $phpFileName = $className . '.php';
        $txtFileName = $className . '.txt';

        $phpFilePath = $this->commandsDir . '/' . $phpFileName;
        $txtFilePath = $this->commandsDir . '/' . $txtFileName;

        if ($action === 'create') {
            if (in_array($commandName, $this->protectedCommands, true)) {
                $io->error("Cannot create command $commandName because it is protected.");
                return Command::FAILURE;
            }

            if ($this->commandExists($commandName)) {
                $io->error("Command $commandName already exists.");
                return Command::FAILURE;
            }

            if (file_exists($phpFilePath) || file_exists($txtFilePath)) {
                $io->error("Command files already exist for $commandName.");
                return Command::FAILURE;
            }

            $description = $io->ask('Enter command description', 'My custom dynamically loaded command');

            // بسازیم کلاس PHP را
            $phpContent = $this->generateCommandClassContent($className, $commandName, $description);

            if (file_put_contents($phpFilePath, $phpContent) === false) {
                $io->error("Failed to write PHP class file $phpFilePath.");
                return Command::FAILURE;
            }

            // بسازیم فایل txt را
            $txtContent = "name=$commandName\n";
            $txtContent .= "description=$description\n";
            $txtContent .= "class=$className\n";

            if (file_put_contents($txtFilePath, $txtContent) === false) {
                $io->error("Failed to write txt metadata file $txtFilePath.");
                return Command::FAILURE;
            }

            $io->success("Command $commandName created.");
            $io->writeln("PHP class file: $phpFilePath");
            $io->writeln("Metadata file: $txtFilePath");
            return Command::SUCCESS;
        }

        if ($action === 'delete') {
            if (in_array($commandName, $this->protectedCommands, true)) {
                $io->error("Command $commandName is protected and cannot be deleted.");
                return Command::FAILURE;
            }

            if (!$this->commandExists($commandName)) {
                $io->error("Command $commandName does not exist.");
                return Command::FAILURE;
            }

            if (!file_exists($phpFilePath) && !file_exists($txtFilePath)) {
                $io->error("Command files do not exist.");
                return Command::FAILURE;
            }

            if (!$io->confirm("Are you sure you want to delete $commandName?", false)) {
                $io->writeln('Deletion cancelled.');
                return Command::SUCCESS;
            }

            if (file_exists($phpFilePath) && !unlink($phpFilePath)) {
                $io->error("Could not delete $phpFilePath.");
                return Command::FAILURE;
            }

            if (file_exists($txtFilePath) && !unlink($txtFilePath)) {
                $io->error("Could not delete $txtFilePath.");
                return Command::FAILURE;
            }

            $io->success("Command $commandName deleted.");
            return Command::SUCCESS;
        }

        $io->error('Invalid action.');
        return Command::FAILURE;
    }

    private function commandNameToClassName(string $commandName): string
    {
        $cleanName = preg_replace('/^laris:/i', '', $commandName);
        $parts = preg_split('/[:_-]/', $cleanName);

        $pascalCase = '';
        foreach ($parts as $part) {
            $pascalCase .= ucfirst(strtolower($part));
        }
        return 'Laris' . $pascalCase;
    }

    private function generateCommandClassContent(string $className, string $commandName, string $description): string
    {
        $descComment = addslashes($description);
        $cmdName = addslashes($commandName);

        return <<<PHP
<?php

namespace Laris\Commands\Laris;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command: $cmdName
 * Description: $descComment
 */
class $className extends Command
{
    protected static \$defaultName = '$cmdName';

    protected function configure(): void
    {
        \$this->setDescription('$descComment');
    }

    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        \$io = new SymfonyStyle(\$input, \$output);
        \$io->writeln('This is the command $cmdName');
        return Command::SUCCESS;
    }
}

PHP;
    }

    private function commandExists(?string $commandName): bool
    {
        if (!$commandName) {
            return false;
        }

        $className = $this->commandNameToClassName($commandName);
        $phpFilePath = $this->commandsDir . '/' . $className . '.php';
        $txtFilePath = $this->commandsDir . '/' . $className . '.txt';

        return file_exists($phpFilePath) || file_exists($txtFilePath);
    }
}
