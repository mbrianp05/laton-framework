<?php

namespace Mbrianp\FuncCollection\Routing;

use Mbrianp\FuncCollection\Http\Request;
use Mbrianp\FuncCollection\Routing\Attribute\Route;

class Router
{
    /**
     * Router constructor.
     * @param array<int, Route> $routes
     */
    public function __construct(
        public array $routes = []
    )
    {
        $this->resolveRoutesParameterNames();
    }

    /**
     * Converts the given Path
     * into a real Math Expression
     * to parse it.
     *
     * /contact/{name} => /contact/(?P<name>([^\/]+))
     *
     * @param string $path
     * @return string
     */
    protected function toMatchExpression(Route $route): string
    {
        $path = $route->path;

        $path = \preg_quote($path);
        $path = str_replace('/', '\/', $path);

        return '/^' . preg_replace('/\\\{([a-z0-9_]+)\\\}/i', '(?P<$1>([^\/]+))', $path) . '$/i';

        // This way is faster
        // foreach ($route->data['__parameters'] as $parameter) {
        //      $path = preg_replace('/{' . $parameter . '}/', '(?P<' . $parameter . '>([^\/]+))', $path);
        //  }
        //
        // return '/' . $path . '$/i';
    }

    protected function resolveRoutesParameterNames(): void
    {
        foreach ($this->routes as $route) {
            preg_match_all('/{([a-z0-9_]+)}/i', $route->path, $parameters);

            $route->data['__parameters'] = $parameters[1];
        }
    }

    protected function resolveParameters(array $unresolvedParameters): array
    {
        $resolvedParameters = [];

        foreach ($unresolvedParameters as $key => $parameter) {
            if (\is_string($key))
                $resolvedParameters[$key] = $parameter;
        }

        return $resolvedParameters;
    }

    protected function satisfiesRequirements(Route $route): bool
    {
        foreach ($route->parameters as $parameter_name => $parameter_value) {
            $requirement = $route->requirements[$parameter_name] ?? null;

            if (null === $requirement) {
                continue;
            }

            if (!preg_match('/' . $requirement . '/i', $parameter_value)) {
                return false;
            }
        }

        return true;
    }

    public function resolveCurrentRoute(Request $request): ?Route
    {
        foreach ($this->routes as $route) {
            if (\in_array($request->method, $route->methods) && preg_match($this->toMatchExpression($route), $request->path, $parameters)) {
                $parameters = $this->resolveParameters($parameters);
                $route->parameters = $parameters;

                if ($this->satisfiesRequirements($route)) {
                    return $route;
                }
            }
        }

        return null;
    }

    public function hasRoutes(): bool
    {
        return !empty($this->routes);
    }
}