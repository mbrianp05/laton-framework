<?php

namespace App\classes\ORM\Drivers;

use PDO;

interface QueryDriverInterface
{
    public const LT = '<';
    public const GT = '>';
    public const E = '=';
    public const ALIKE = 'LIKE';

    public function __construct(PDO $connection, string|array $fields, string $table);

    public function where(string $field, string|int|float $value, string $operator = self::E): static;

    public function orWhere(string $field, string|int|float $value, string $operator = self::E): static;

    public function andWhere(string $field, string|int|float $value, string $operator = self::E): static;

    public function limit(int $limit): static;

    public function orderBy(array $order = []): static;

    public function getSingleResult(): object;

    public function getOneOrNullResult(): object|null;

    public function getResult(): array|object|null;
}