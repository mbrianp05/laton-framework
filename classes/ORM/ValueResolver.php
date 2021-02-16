<?php

namespace Mbrianp\FuncCollection\ORM;

interface ValueResolver
{
    public function resolve(array $values): mixed;
}