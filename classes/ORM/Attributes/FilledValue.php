<?php

namespace Mbrianp\FuncCollection\ORM\Attributes;

use Attribute;

/**
 * When the value of the column is the value of another columns
 * You can use this attribute to define it.
 *
 * Example:
 *      #[Column]
 *      public string $name;
 *
 *      #[Column]
 *      public string $lastname;
 *
 *      #[Column]
 *      #[FilledValue(['name', 'lastname'])]
 *      public string $fullName;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FilledValue
{
    public function __construct(
        public array $columns,
        public string $pattern,
    )
    {
    }
}