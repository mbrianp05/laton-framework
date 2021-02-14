<?php

namespace Mbrianp\FuncCollection\DIC;

class Service
{
    public function __construct(
        public string $id,
        public string $class,
        public array $params = [],
    )
    {
    }
}