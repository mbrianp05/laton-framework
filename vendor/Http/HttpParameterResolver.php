<?php

namespace Mbrianp\FuncCollection\Http;

use Mbrianp\FuncCollection\DIC\DIC;
use Mbrianp\FuncCollection\Kernel\ParameterResolver;
use ReflectionParameter;

class HttpParameterResolver implements ParameterResolver
{
    public function __construct(protected DIC $dependenciesContainer)
    {
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        return Request::class == $parameter->getType()->getName();
    }

    public function resolve(): object
    {
        return $this->dependenciesContainer->getService('http.request');
    }
}