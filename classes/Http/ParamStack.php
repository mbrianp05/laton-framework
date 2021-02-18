<?php

namespace Mbrianp\FuncCollection\Http;

class ParamStack
{
    public function __construct(protected array $data = []) {}

    public function get(string $key, string $default = ''): string
    {
        return $this->data[$key] ?? $default;
    }

    public function exists(string $key): bool
    {
        return $this->__isset($key);
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }
}