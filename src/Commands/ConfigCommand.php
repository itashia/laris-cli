<?php

namespace Laris\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigCommand extends Command
{
    /**
     * The default name of the command.
     *
     * @var string
     */
    protected static $defaultName = 'laris:config';

    /**
     * Configure the command arguments and description.
     *
     * Defines the 'action' argument (required) with possible values:
     * get, set, remove, list
     * Also defines optional 'key' and 'value' arguments.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('laris:config')
            ->setDescription('Manage project configuration settings')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: get, set, remove, list')
            ->addArgument('key', InputArgument::OPTIONAL, 'Configuration key')
            ->addArgument('value', InputArgument::OPTIONAL, 'Configuration value (for set)');
    }

    /**
     * Get the path of the configuration file.
     *
     * @return string Full path to the .larisconfig.json file in the current directory.
     */
    private function getConfigPath(): string
    {
        return getcwd() . '/.larisconfig.json';
    }

    /**
     * Load the configuration from the JSON file.
     *
     * Returns an associative array of configuration data.
     * If the file doesn't exist or JSON is invalid, returns an empty array.
     *
     * @return array Configuration key-value pairs.
     */
    private function loadConfig(): array
    {
        $path = $this->getConfigPath();
        if (!file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Save the configuration array to the JSON file.
     *
     * @param array $config Associative array of configuration data to save.
     * @return void
     */
    private function saveConfig(array $config): void
    {
        $path = $this->getConfigPath();
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Execute the configuration management command.
     *
     * Supports actions:
     * - get: Retrieve the value of a given key.
     * - set: Set the value of a given key.
     * - remove: Remove a key from configuration.
     * - list: List all configuration key-value pairs.
     *
     * @param InputInterface  $input  The input interface instance.
     * @param OutputInterface $output The output interface instance.
     * @return int Exit status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $config = $this->loadConfig();

        switch ($action) {
            case 'get':
                if (!$key) {
                    $io->error('Please provide the configuration key to get.');
                    return Command::FAILURE;
                }
                if (!array_key_exists($key, $config)) {
                    $io->warning("Key '$key' not found in configuration.");
                    return Command::SUCCESS;
                }
                $io->writeln("$key = " . json_encode($config[$key], JSON_UNESCAPED_SLASHES));
                break;

            case 'set':
                if (!$key || $value === null) {
                    $io->error('Please provide both key and value for set action.');
                    return Command::FAILURE;
                }
                // Try to decode JSON value, fallback to string if invalid
                $decodedValue = json_decode($value, true);
                $config[$key] = $decodedValue === null && strtolower($value) !== 'null' ? $value : $decodedValue;
                $this->saveConfig($config);
                $io->success("Key '$key' set to: " . json_encode($config[$key], JSON_UNESCAPED_SLASHES));
                break;

            case 'remove':
                if (!$key) {
                    $io->error('Please provide the configuration key to remove.');
                    return Command::FAILURE;
                }
                if (!array_key_exists($key, $config)) {
                    $io->warning("Key '$key' not found in configuration.");
                    return Command::SUCCESS;
                }
                unset($config[$key]);
                $this->saveConfig($config);
                $io->success("Key '$key' removed from configuration.");
                break;

            case 'list':
                if (empty($config)) {
                    $io->writeln('No configuration found.');
                    return Command::SUCCESS;
                }
                $io->title('Project Configuration');
                foreach ($config as $k => $v) {
                    $io->writeln("$k = " . json_encode($v, JSON_UNESCAPED_SLASHES));
                }
                break;

            default:
                $io->error("Unknown action '$action'. Allowed actions: get, set, remove, list.");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
