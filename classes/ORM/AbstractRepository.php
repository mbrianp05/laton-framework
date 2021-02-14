<?php

namespace Mbrianp\FuncCollection\ORM;

use Mbrianp\FuncCollection\ORM\Drivers\DatabaseDriverInterface;

abstract class AbstractRepository
{
    public function __construct(protected DatabaseDriverInterface $driver)
    {
    }
}