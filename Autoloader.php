<?php

class Autoloader
{
    public function __construct(protected array $config = [])
    {
    }

    protected function psr4NamespaceFileResolver(string $fullyQualifiedName): string
    {
        foreach ($this->config as $namespace => $dir) {
            if (($target = \str_replace($namespace, '', $fullyQualifiedName)) != $fullyQualifiedName) {
                return $dir . $target;
            }
        }

        return $fullyQualifiedName;
    }

    protected function load(string $classname): void
    {
        $file = $this->psr4NamespaceFileResolver($classname);

        require_once __DIR__ . '/' . $file . '.php';
    }

    public function run(): void
    {
        spl_autoload_register([$this, 'load']);
    }
}