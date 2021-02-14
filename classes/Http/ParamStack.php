<?php

namespace Mbrianp\FuncCollection\Http;

class ParamStack
{
    public function __construct(protected array $data = []) {}

    public function get(string $key, string $default = null): ?string
    {
        return $this->data[$key] ?? $default;
    }
}