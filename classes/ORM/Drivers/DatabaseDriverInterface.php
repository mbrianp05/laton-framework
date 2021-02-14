<?php

namespace Mbrianp\FuncCollection\ORM\Drivers;

use App\classes\ORM\Drivers\QueryDriverInterface;
use Mbrianp\FuncCollection\ORM\Schema;
use PDO;

interface DatabaseDriverInterface
{
    public function select(string $table, string $fields): QueryDriverInterface;

    public function insert(string $table, array $values): bool;

    public function createTable(Schema $schema): bool;

    public function createDatabase(string $name): bool;

    public function addSQL(string $sql): void;

    public function do(): void;

    public function __construct(PDO $connection);
}