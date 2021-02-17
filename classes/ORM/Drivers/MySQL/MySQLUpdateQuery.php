<?php

namespace Mbrianp\FuncCollection\ORM\Drivers\MySQL;

use Mbrianp\FuncCollection\ORM\Drivers\UpdateDriverInterface;
use PDO;

class MySQLUpdateQuery extends AbstractMySQLQuery implements UpdateDriverInterface
{
    public function __construct(PDO $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }


}