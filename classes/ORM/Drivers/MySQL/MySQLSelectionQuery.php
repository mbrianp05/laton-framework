<?php

namespace Mbrianp\FuncCollection\ORM\Drivers\MySQL;

use Mbrianp\FuncCollection\ORM\Drivers\SelectionDriverInterface;
use Mbrianp\FuncCollection\ORM\Helper\Selection;
use PDO;
use RuntimeException;

class MySQLSelectionQuery extends AbstractMySQLQuery implements SelectionDriverInterface
{
    protected Selection $selection;
    protected ?int $limit = null;
    protected array $orderBy = [];

    public function __construct(PDO $connection, string $table, array|string|null $fields = [])
    {
        parent::__construct($connection, $table);
        
        // Convert $fields to array
        if (empty($fields)) {
            $fields = [];
        } elseif (\is_string($fields)) {
            $fields = [$fields];
        }

        $this->selection = new Selection($fields);
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function orderBy(array $order = []): static
    {
        $this->orderBy = $order;

        return $this;
    }

    public function getSingleResult(): array
    {
        $results = $this->getResults();

        if (count($results) != 1) {
            throw new RuntimeException('No result was found');
        }

        return $results[0];
    }

    public function getOneOrNullResult(): array|null
    {
        $results = $this->getResults();

        if (empty($results)) {
            return null;
        }

        return $results;
    }

    public function getResults(): array
    {
        $sql = $this->createSQL();

        return $this->executeQuery($sql);
    }

    protected function createSQL(): string
    {
        $sql = 'SELECT ';

        if (empty($this->selection->fields)) {
            $sql .= '*';
        } else {
            $sql .= implode(', ', $this->selection->fields);
        }

        $whereCounter = 0;
        $sql .= ' FROM ' . $this->table;

        if (0 != count($this->conditions)) {
            foreach ($this->conditions as $condition) {
                $sql .= match ($condition->type) {
                    'OR' => ' OR WHERE',
                    'AND' => ' AND WHERE',
                    null => ' WHERE',
                };

                if (null == $condition->type) {
                    $whereCounter++;

                    if ($whereCounter > 1) {
                        throw new RuntimeException('Cannot declare two where filters, once declared one, just orWhere and andWhere are allowed');
                    }
                }

                $sql .= ' ' . $condition->column . ' ' . $condition->operator . ' ' . \var_export($condition->value, true);
            }
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';

            foreach ($this->orderBy as $column => $order) {
                $sql .= $column . ' ' . $order . ', ';
            }

            $sql = substr($sql, 0, -2);
        }

        if (null !== $this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        return $sql;
    }

    public function executeQuery(string $query): array
    {
        return $this->connection->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}