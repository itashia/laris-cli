<?php

namespace Laris\Commands;

use Laris\Library\DB\LarisDB;
use Laris\Library\DB\TableManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DbCommand
 *
 * Command line tool for managing the database via LarisDB.
 * Supports actions like listing tables, describing tables, 
 * selecting, inserting, updating, deleting records and generating fake data.
 */
class DbCommand extends Command
{
    /**
     * The default command name.
     * 
     * @var string
     */
    protected static $defaultName = 'laris:db';

    /**
     * @var LarisDB|null Database connection instance.
     */
    private ?LarisDB $db = null;

    /**
     * Configure command name, description, arguments, and options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('laris:db')
            ->setDescription('Database management commands using LarisDB')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: list-tables, describe-table, select, insert, update, delete, generate-fake')
            ->addArgument('table', InputArgument::OPTIONAL, 'Table name')
            ->addOption('where', null, InputOption::VALUE_OPTIONAL, 'WHERE conditions (e.g. id=1,name=ali)')
            ->addOption('order', null, InputOption::VALUE_OPTIONAL, 'Order by (e.g. id DESC)')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records', 10)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset for records', 0)
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL, 'Fields to select, comma separated', '*')
            ->addOption('data', null, InputOption::VALUE_OPTIONAL, 'Data in JSON format for insert/update')
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Count of fake data to generate', 10);
    }

    /**
     * Executes the command logic.
     *
     * @param InputInterface  $input  Input parameters.
     * @param OutputInterface $output Output interface.
     * @return int Exit code (0 for success).
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Load DB configuration - replace with your actual config or load from env/file
        $config = [
            'driver' => 'mysql',  // or pgsql, sqlite, sqlsrv
            'host' => '127.0.0.1',
            'database' => 'your_database',
            'username' => 'your_user',
            'password' => 'your_password',
        ];

        try {
            $this->db = new LarisDB($config);
        } catch (\Exception $e) {
            $io->error("Database connection failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $action = strtolower($input->getArgument('action'));
        $table = $input->getArgument('table');
        $where = $input->getOption('where');
        $order = $input->getOption('order');
        $limit = (int)$input->getOption('limit');
        $offset = (int)$input->getOption('offset');
        $fields = $input->getOption('fields');
        $dataJson = $input->getOption('data');
        $count = (int)$input->getOption('count');

        try {
            switch ($action) {
                case 'list-tables':
                    $tables = $this->db->getTables();
                    $io->title("Tables in database `{$config['database']}`:");
                    $io->listing($tables);
                    break;

                case 'describe-table':
                    if (!$table) {
                        $io->error('Table name is required for describe-table');
                        return Command::FAILURE;
                    }
                    $columns = $this->db->getColumns($table);
                    $io->title("Columns of table `$table`:");
                    $io->table(array_keys($columns[0] ?? []), $columns);
                    break;

                case 'select':
                    if (!$table) {
                        $io->error('Table name is required for select');
                        return Command::FAILURE;
                    }
                    $manager = $this->db->table($table);

                    $fieldsArr = $fields === '*' ? ['*'] : explode(',', $fields);
                    $manager->select($fieldsArr);

                    if ($where) {
                        foreach ($this->parseWhere($where) as $cond) {
                            $manager->where($cond['column'], $cond['operator'], $cond['value']);
                        }
                    }

                    if ($order) {
                        $parts = explode(' ', $order);
                        $col = $parts[0];
                        $dir = $parts[1] ?? 'ASC';
                        $manager->orderBy($col, $dir);
                    }

                    $manager->limit($limit)->offset($offset);

                    $results = $manager->get();
                    $io->title("Selected records from `$table`:");
                    if (count($results) === 0) {
                        $io->warning("No records found.");
                    } else {
                        $io->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                    break;

                case 'insert':
                    if (!$table) {
                        $io->error('Table name is required for insert');
                        return Command::FAILURE;
                    }
                    if (!$dataJson) {
                        $io->error('Data JSON is required for insert');
                        return Command::FAILURE;
                    }
                    $data = json_decode($dataJson, true);
                    if (!is_array($data)) {
                        $io->error('Invalid JSON data');
                        return Command::FAILURE;
                    }
                    $manager = $this->db->table($table);
                    $manager->insert($data);
                    $io->success("Record inserted successfully.");
                    break;

                case 'update':
                    if (!$table) {
                        $io->error('Table name is required for update');
                        return Command::FAILURE;
                    }
                    if (!$dataJson) {
                        $io->error('Data JSON is required for update');
                        return Command::FAILURE;
                    }
                    if (!$where) {
                        $io->error('Where condition is required for update');
                        return Command::FAILURE;
                    }
                    $data = json_decode($dataJson, true);
                    if (!is_array($data)) {
                        $io->error('Invalid JSON data');
                        return Command::FAILURE;
                    }
                    $manager = $this->db->table($table);
                    foreach ($this->parseWhere($where) as $cond) {
                        $manager->where($cond['column'], $cond['operator'], $cond['value']);
                    }
                    $manager->update($data);
                    $io->success("Record(s) updated successfully.");
                    break;

                case 'delete':
                    if (!$table) {
                        $io->error('Table name is required for delete');
                        return Command::FAILURE;
                    }
                    if (!$where) {
                        $io->error('Where condition is required for delete');
                        return Command::FAILURE;
                    }
                    $manager = $this->db->table($table);
                    foreach ($this->parseWhere($where) as $cond) {
                        $manager->where($cond['column'], $cond['operator'], $cond['value']);
                    }
                    $manager->delete();
                    $io->success("Record(s) deleted successfully.");
                    break;

                case 'generate-fake':
                    if (!$table) {
                        $io->error('Table name is required for generate-fake');
                        return Command::FAILURE;
                    }
                    $manager = $this->db->table($table);
                    $manager->generateFakeData($count);
                    $io->success("Generated {$count} fake records for table `$table`.");
                    break;

                default:
                    $io->error("Unknown action: $action");
                    return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Parse a simple where string like "id=1,name!=ali" into structured conditions.
     *
     * Supports operators: =, !=, <, >, <=, >=
     *
     * @param string $where Raw where string.
     * @return array Parsed conditions as arrays with keys: column, operator, value.
     */
    private function parseWhere(string $where): array
    {
        $conditions = [];
        $parts = explode(',', $where);
        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(=|!=|<=|>=|<|>)(.+)$/', trim($part), $matches)) {
                $conditions[] = [
                    'column' => $matches[1],
                    'operator' => $matches[2],
                    'value' => $matches[3],
                ];
            }
        }
        return $conditions;
    }
}
