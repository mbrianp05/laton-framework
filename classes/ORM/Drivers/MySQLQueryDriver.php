<?php

namespace Mbrianp\FuncCollection\ORM\Drivers;

use LogicException;
use PDO;

class MySQLQueryDriver implements QueryDriverInterface
{
    /**
     * Marks if where() method has been called
     */
    protected bool $isWhere = false;

    protected string $sql = '';

    public function __construct(protected PDO $connection, null|array|string $fields, protected string $table)
    {
        // Fields parameter whatever it is (null or string or array) will be converted into an array
        // If it's null will be an empty array
        // If it's an empty string will also be an empty array
        // If it's not an empty string will be an array with that string in the array

        if (null === $fields) {
            $fields = '';
        }

        if (is_string($fields)) {
            if (!empty($fields)) {
                $fields = [$fields];
            } else {
                $fields = [];
            }
        }

        $this->initSelect($fields);
    }

    public function initSelect(array $fields): void
    {
        if (empty($fields)) {
            $selection = '*';
        } else {
            $selection = implode(', ', $fields);
        }

        $this->sql = 'SELECT ' . $selection . ' FROM ' . $this->table;
    }

    protected function getCondition(string $field, string|float|int $value, string $operator = self::E): string
    {
        return $field . $operator . \var_export($value, true);
    }

    public function where(string $field, string|float|int $value, string $operator = self::E): static
    {
        $this->isWhere = true;

        $this->sql .= ' WHERE ' . $this->getCondition($field, $value, $operator);

        return $this;
    }

    public function orWhere(string $field, string|float|int $value, string $operator = self::E): static
    {
        if (!$this->isWhere) {
            throw new LogicException('Cannot set orWhere condition without having set a where before.');
        }

        $this->sql .= ' OR WHERE ' . $this->getCondition($field, $value, $operator);

        return $this;
    }

    public function andWhere(string $field, string|float|int $value, string $operator = self::E): static
    {
        if (!$this->isWhere) {
            throw new LogicException('Cannot set orWhere condition without having set a where before.');
        }

        $this->sql .= ' AND WHERE ' . $this->getCondition($field, $value, $operator);

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->sql .= ' LIMIT ' . $limit;

        return $this;
    }

    public function orderBy(array $order = []): static
    {
        if (!empty($order)) {
            $this->sql .= ' ORDER BY';

            foreach ($order as $field => $ord) {
                $this->sql .= ' ' . $field . ' ' . $ord;
            }
        }

        return $this;
    }

    protected function do(): array|null
    {
        return $this->connection->query($this->sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSingleResult(): array
    {
        $results = $this->do();
    }

    public function getOneOrNullResult(): array|null
    {
        $results = $this->do();

        return $results[0] ?? null;
    }

    public function getResults(): array|null
    {
        return $this->do();
    }
}