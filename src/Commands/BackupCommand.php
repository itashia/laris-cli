<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BackupCommand extends Command
{
    /**
     * The default name of the command.
     *
     * @var string
     */
    protected static $defaultName = 'laris:backup';

    /**
     * Configure the command name and description.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('laris:backup')
            ->setDescription('Backup the current project excluding vendor and node_modules directories');
    }

    /**
     * Execute the backup process.
     *
     * Creates a zip archive of the current project directory,
     * excluding specified folders and files such as vendor, node_modules, .git, .env, and backups folder.
     *
     * @param InputInterface  $input  The input interface instance.
     * @param OutputInterface $output The output interface instance.
     * @return int Exit code representing success or failure.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectDir = getcwd();
        $backupDir = $projectDir . '/backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $backupFile = $backupDir . "/backup_{$timestamp}.zip";

        $io->writeln("Creating backup...");

        $zip = new \ZipArchive();

        if ($zip->open($backupFile, \ZipArchive::CREATE) !== true) {
            $io->error("Cannot create backup file at $backupFile");
            return Command::FAILURE;
        }

        $exclude = [
            'vendor',
            'node_modules',
            '.git',
            '.env',
            'backups',
        ];

        $this->addFolderToZip($zip, $projectDir, $projectDir, $exclude);

        $zip->close();

        $io->success("Backup created successfully at: $backupFile");

        return Command::SUCCESS;
    }

    /**
     * Recursively add files from the specified folder to the Zip archive,
     * while excluding directories or files defined in the exclude list.
     *
     * @param \ZipArchive $zip       The ZipArchive instance to add files into.
     * @param string      $folderPath The folder path to scan for files.
     * @param string      $basePath   The base path used to calculate relative paths inside the archive.
     * @param array       $exclude    List of directories or files to exclude.
     * @return void
     */
    private function addFolderToZip(\ZipArchive $zip, string $folderPath, string $basePath, array $exclude): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();

            $relativePath = substr($filePath, strlen($basePath) + 1);

            foreach ($exclude as $ex) {
                if (strpos($relativePath, $ex) === 0 || strpos($relativePath, "/$ex") !== false) {
                    continue 2;
                }
            }

            if ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
