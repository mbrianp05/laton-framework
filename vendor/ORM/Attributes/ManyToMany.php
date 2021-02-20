<?php


namespace Mbrianp\FuncCollection\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany
{
    public function __construct(public string $targetEntity, public ?string $mappedBy = null)
    {
    }
}