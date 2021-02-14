<?php

namespace Mbrianp\FuncCollection\Http;

class Request
{
    public function __construct(
        public ParamStack $query,
        public ParamStack $post,
        public string $path,
        public string $method,
    )
    {
    }

    public static function createFromGlobals(): static
    {
        return new static(new ParamStack($_GET), new ParamStack($_POST), $_SERVER['PATH_INFO'] ?? '/', $_SERVER['REQUEST_METHOD']);
    }
}