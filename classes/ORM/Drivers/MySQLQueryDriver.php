<?php

namespace App\classes\ORM\Drivers;

use LogicException;
use PDO;

class MySQLQueryDriver implements QueryDriverInterface
{
    /**
     * Marks if where() method has been called
     */
    protected bool $isWhere = false;

    protected string $sql = '';

    public function __construct(protected PDO $connection, array|string $fields, protected string $table)
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $this->initSelect($fields);
    }

    public function initSelect(array $fields): void
    {
        $this->sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $this->table;
    }

    protected function getCondition(string $field, string|float|int $value, string $operator = self::E): string
    {
        return $field . $operator . \var_export($value);
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
            $this->sql .= 'ORDER BY';

            foreach ($order as $field => $ord) {
                $this->sql .= ' ' . $field . ' ' . $ord;
            }
        }

        return $this;
    }

    protected function do(): void
    {

    }

    public function getSingleResult(): object
    {
    }

    public function getOneOrNullResult(): object|null
    {
    }

    public function getResult(): array|object|null
    {
    }
}