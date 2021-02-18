<?php

namespace Mbrianp\FuncCollection\Http;

use Mbrianp\FuncCollection\DIC\DependenciesDefinitionInterface;
use Mbrianp\FuncCollection\DIC\DIC;
use Mbrianp\FuncCollection\DIC\Service;

class HttpDependenciesDefinition implements DependenciesDefinitionInterface
{
    public function __construct(DIC $dependenciesContainer, array $config)
    {
    }

    public function getServices(): array
    {
        $get = new ParamStack($_GET);
        $post = new ParamStack($_POST);

        $params = [$get, $post, $_SERVER['PATH_INFO'] ?? '/', $_SERVER['REQUEST_METHOD']];

        return [new Service('http.request', Request::class, $params)];
    }
}