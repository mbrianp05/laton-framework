<?php

namespace Mbrianp\FuncCollection\ORM\Drivers\MySQL;

use LogicException;
use Mbrianp\FuncCollection\ORM\Helper\Condition;
use PDO;

abstract class AbstractMySQLQuery
{
    /**
     * Marks if where() method has been called
     */
    protected bool $isWhere = false;

    /**
     * @var Condition[] $conditions
     */
    protected array $conditions = [];

    protected string $sql = '';

    public function __construct(protected PDO $connection, protected string $table)
    {
    }

    public function where(string $field, string|float|int $value, string $operator = Condition::E): static
    {
        $this->conditions[] = new Condition($field, $value, $operator);

        return $this;
    }

    public function orWhere(string $field, string|float|int $value, string $operator = Condition::E): static
    {
        if (!$this->isWhere) {
            throw new LogicException('Cannot call ' . __METHOD__ . ' method without having set a where before.');
        }

        $this->conditions[] = new Condition($field, $value, $operator, Condition::CONDITION_TYPES['or']);

        return $this;
    }

    public function andWhere(string $field, string|float|int $value, string $operator = Condition::E): static
    {
        if (!$this->isWhere) {
            throw new LogicException('Cannot call ' . __METHOD__ . ' method without having set a where before.');
        }

        $this->conditions[] = new Condition($field, $value, $operator, Condition::CONDITION_TYPES['or']);

        return $this;
    }
}