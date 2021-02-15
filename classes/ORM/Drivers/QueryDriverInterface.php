<?php

namespace Mbrianp\FuncCollection\ORM\Drivers;

use PDO;

interface QueryDriverInterface
{
    public const LT = '<';
    public const GT = '>';
    public const E = '=';
    public const ALIKE = 'LIKE';

    public function __construct(PDO $connection, null|string|array $fields, string $table);

    public function where(string $field, string|int|float $value, string $operator = self::E): static;

    public function orWhere(string $field, string|int|float $value, string $operator = self::E): static;

    public function andWhere(string $field, string|int|float $value, string $operator = self::E): static;

    public function limit(int $limit): static;

    public function orderBy(array $order = []): static;

    /**
     * Returns the results in a array
     * where key is the column and the value is the value from
     * the database.
     *
     * If any result was not found then an exception should be thrown
     */
    public function getSingleResult(): array;

    /**
     * Returns a the first found value from the database
     * or null if no one was found
     */
    public function getOneOrNullResult(): array|null;

    /**
     * Return all the found results or null if no one was found.
     */
    public function getResults(): array|null;
}