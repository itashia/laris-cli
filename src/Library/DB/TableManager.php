<?php

namespace Laris\Library\DB;

use Laris\Library\DB\LarisDB;


class TableManager
{
    private LarisDB $db;
    private string $table;
    private array $conditions = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $fields = ['*'];

    public function __construct(LarisDB $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->conditions[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function get(): array
    {
        $query = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table}";
        $params = [];

        if (!empty($this->conditions)) {
            $whereClauses = [];
            foreach ($this->conditions as $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
                $params[] = $condition['value'];
            }
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $query .= " ORDER BY " . implode(', ', $orderParts);
        }

        if ($this->limit !== null) {
            $query .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $query .= " OFFSET " . $this->offset;
        }

        return $this->db->query($query, $params)->execute();
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->db->query($query, array_values($data))->execute();
        return true;
    }

    public function update(array $data): bool
    {
        if (empty($this->conditions)) {
            throw new RuntimeException("Update requires WHERE conditions for safety");
        }

        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }

        $whereClauses = [];
        foreach ($this->conditions as $condition) {
            $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
            $params[] = $condition['value'];
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $setParts) . 
                 " WHERE " . implode(' AND ', $whereClauses);
        
        $this->db->query($query, $params)->execute();
        return true;
    }

    public function delete(): bool
    {
        if (empty($this->conditions)) {
            throw new RuntimeException("Delete requires WHERE conditions for safety");
        }

        $whereClauses = [];
        $params = [];
        
        foreach ($this->conditions as $condition) {
            $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
            $params[] = $condition['value'];
        }

        $query = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereClauses);
        $this->db->query($query, $params)->execute();
        return true;
    }

    public function count(): int
    {
        $result = $this->select(['COUNT(*) as count'])->first();
        return (int) ($result['count'] ?? 0);
    }

    public function generateFakeData(int $count = 10): bool
    {
        $columns = $this->db->getColumns($this->table);
        $columnNames = array_column($columns, isset($columns[0]) ? key($columns[0]) : 0);

        for ($i = 0; $i < $count; $i++) {
            $data = [];
            foreach ($columnNames as $column) {
                if ($column === 'id') continue;
                
                // Simple fake data generator
                $data[$column] = $this->generateFakeValue($column);
            }
            
            $this->insert($data);
        }

        return true;
    }

    private function generateFakeValue(string $columnName)
    {
        // Simple fake data generator based on column name
        if (stripos($columnName, 'email') !== false) {
            return 'user' . rand(1, 1000) . '@example.com';
        } elseif (stripos($columnName, 'name') !== false) {
            $names = ['Ali', 'Mohammad', 'Sara', 'Fatemeh', 'Reza', 'Zahra'];
            return $names[array_rand($names)];
        } elseif (stripos($columnName, 'date') !== false) {
            return date('Y-m-d', strtotime('-' . rand(0, 365) . ' days'));
        } elseif (stripos($columnName, 'time') !== false) {
            return date('H:i:s');
        } elseif (stripos($columnName, 'price') !== false || stripos($columnName, 'amount') !== false) {
            return rand(1000, 100000) / 100;
        } else {
            return 'Sample data for ' . $columnName;
        }
    }

    public function toJson(): string
    {
        return json_encode($this->get(), JSON_PRETTY_PRINT);
    }

    public function describe(): array
    {
        return $this->db->getColumns($this->table);
    }
}