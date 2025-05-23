<?php

namespace Laris\Library\DB;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;
use JsonSerializable;

class LarisDB implements JsonSerializable
{
    private PDO $connection;
    private string $driver;
    private array $config;
    private ?string $lastQuery = null;
    private array $lastParams = [];
    private array $supportedDrivers = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];

    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        $this->driver = strtolower($config['driver']);
        $this->connect();
    }

    private function validateConfig(array $config): void
    {
        $required = ['driver', 'host', 'database', 'username', 'password'];
        
        if (!in_array(strtolower($config['driver']), $this->supportedDrivers, true)) {
            throw new InvalidArgumentException(
                "Unsupported database driver. Supported drivers are: " . 
                implode(', ', $this->supportedDrivers)
            );
        }

        foreach ($required as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException("Missing required configuration: {$key}");
            }
        }
    }

    private function connect(): void
    {
        $dsn = $this->buildDsn();
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (PDOException $e) {
            throw new RuntimeException("Connection failed: " . $e->getMessage());
        }
    }

    private function buildDsn(): string
    {
        switch ($this->driver) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $this->config['host'],
                    $this->config['database']
                );
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;dbname=%s',
                    $this->config['host'],
                    $this->config['database']
                );
            case 'sqlite':
                return 'sqlite:' . $this->config['database'];
            case 'sqlsrv':
                return sprintf(
                    'sqlsrv:Server=%s;Database=%s',
                    $this->config['host'],
                    $this->config['database']
                );
            default:
                throw new InvalidArgumentException("Unsupported database driver");
        }
    }

    public function query(string $sql, array $params = []): self
    {
        $this->lastQuery = $sql;
        $this->lastParams = $params;
        return $this;
    }

    public function execute(): array
    {
        if (!$this->lastQuery) {
            throw new RuntimeException("No query to execute");
        }

        try {
            $stmt = $this->connection->prepare($this->lastQuery);
            $stmt->execute($this->lastParams);
            
            $result = $stmt->fetchAll();
            $this->lastQuery = null;
            $this->lastParams = [];
            
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    public function first(): ?array
    {
        $results = $this->execute();
        return $results[0] ?? null;
    }

    public function table(string $table): TableManager
    {
        return new TableManager($this, $table);
    }

    public function getTables(): array
    {
        switch ($this->driver) {
            case 'mysql':
                $query = "SHOW TABLES";
                break;
            case 'pgsql':
                $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
                break;
            case 'sqlite':
                $query = "SELECT name FROM sqlite_master WHERE type='table'";
                break;
            case 'sqlsrv':
                $query = "SELECT table_name FROM information_schema.tables";
                break;
            default:
                throw new RuntimeException("Unsupported driver for table listing");
        }

        $tables = $this->query($query)->execute();
        return array_column($tables, isset($tables[0]) ? key($tables[0]) : 0);
    }

    public function getColumns(string $table): array
    {
        switch ($this->driver) {
            case 'mysql':
                $query = "DESCRIBE {$table}";
                break;
            case 'pgsql':
                $query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?";
                break;
            case 'sqlite':
                $query = "PRAGMA table_info({$table})";
                break;
            case 'sqlsrv':
                $query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?";
                break;
            default:
                throw new RuntimeException("Unsupported driver for column listing");
        }

        return $this->query($query, [$table])->execute();
    }

    public function jsonSerialize(): array
    {
        return [
            'driver' => $this->driver,
            'database' => $this->config['database'],
            'host' => $this->config['host'],
            'tables' => $this->getTables(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
