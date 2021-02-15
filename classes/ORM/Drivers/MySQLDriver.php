<?php

namespace Mbrianp\FuncCollection\ORM\Drivers;

use LogicException;
use Mbrianp\FuncCollection\ORM\Attributes\Column;
use Mbrianp\FuncCollection\ORM\Schema;
use PDO;

class MySQLDriver implements DatabaseDriverInterface
{
    private array $statements = [];

    public function __construct(protected PDO $connection)
    {
    }

    public function select(string $table, string|array|null $fields = null): MySQLQueryDriver
    {
        return new MySQLQueryDriver($this->connection, $fields, $table);
    }

    /**
     * @param string $table
     * @param array<string|int, string|int> $values
     * @return bool
     *
     * When you assign a value to a column, should be like this:
     *      insert('table_name', ['name' => 'Me', 'Other value')
     *
     * You can also insert a value to some unnamed column.
     */
    public function insert(string $table, array $values): bool
    {
        $sql = 'INSERT INTO ' . $table . '(';

        $resolvedColumns = [];

        foreach ($values as $column => $value) {
            if (\is_int($column)) {
                $resolvedColumns[] = '?';
            } else {
                $resolvedColumns[] = $column;
            }
        }

        $validCodes = \array_map(fn(?string $value): string => \var_export($value, true), $values);
        $sql .= \implode(', ', $resolvedColumns) . ') VALUES (' . \implode(', ', $validCodes) . ')';

        $this->addSQL($sql);

        try {
            $this->do();
        } catch (LogicException $e) {
            die($e->getMessage());
        }

        return true;
    }

    public function createTable(Schema $schema): bool
    {
        // CREATE TABLE users ( `id` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`)) ENGINE = InnoDB;
        $name = $schema->table->name;
        $sql = 'CREATE TABLE ' . $name . ' ( ';

        foreach ($schema->columns as $column) {
            $sql .= $column->name . ' ' . static::resolveType($column->type);

            if ('VARCHAR' == static::resolveType($column->type)) {
                $sql .= '(' . $column->length . ')';
            }

            if (!$column->nullable) {
                $sql .= ' NOT NULL';
            }

            $isOption = fn (string $option, Column $column): bool => isset($column->options[$option]) && true == $column->options[$option];

            if ($isOption('AUTO_INCREMENTS', $column)) {
                $sql .= ' AUTO_INCREMENT';

                // Now this is false so the next if will active
                $column->options['AUTO_INCREMENTS'] = false;
            }

            if ($isOption('PRIMARY_KEY', $column)) {
                // Only happens if PRIMARY_KEY IS ENABLED
                $sql .= ' , PRIMARY KEY (' . $column->name . ')';
            }

            if (true == $column->unique) {
                $sql .= ' , UNIQUE INDEX ' . \strtoupper(\uniqid('UNIQ_')) . ' (' . $column->name . ') ';
            }

            $sql .= ' , ';
        }

        $sql = substr($sql, 0, -2) . ') ENGINE = InnoDB';
        $this->addSQL($sql);

        try {
            $this->do();
        } catch (LogicException $e) {
            die($e->getMessage());
        }

        return true;
    }

    public function createDatabase(string $name): bool
    {
        $this->addSQL(\sprintf('CREATE DATABASE %s', $name));

        try {
            $this->do();
        } catch (LogicException $e) {
            die($e->getMessage());
        }

        return true;
    }

    /**
     * Stores a SQL statement to be executed by DO method
     */
    public function addSQL(string $sql): void
    {
        $this->statements[] = $sql;
    }

    /**
     * Executes all the stored SQL statements
     * Returns true if everything goes ok,
     * false otherwise.
     */
    public function do(): void
    {
        foreach ($this->statements as $statement) {
            if (!$this->connection->exec($statement)) {
                throw new LogicException($this->connection->errorCode());
            }
        }
    }

    public static function resolveType(string $phptype): string
    {
        return match($phptype) {
            'string' => 'VARCHAR',
            'integer' => 'int',
            default => $phptype,
        };
    }
}