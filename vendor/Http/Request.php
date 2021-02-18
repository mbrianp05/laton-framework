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

    public function input(string $key, string $default = ''): string
    {
        return $this->post->get($key, $default);
    }

    public function get(string $key, string $default = ''): string
    {
        return match (true) {
            $this->query->exists($key) => $this->query->get($key),
            $this->post->exists($key) => $this->post->get($key),
            default => $default,
        };
    }

    public static function createFromGlobals(): static
    {
        return new static(new ParamStack($_GET), new ParamStack($_POST), $_SERVER['PATH_INFO'] ?? '/', $_SERVER['REQUEST_METHOD']);
    }
}