<?php

namespace Mbrianp\FuncCollection\Kernel;

use Mbrianp\FuncCollection\DIC\DIC;
use Mbrianp\FuncCollection\DIC\Service;
use Mbrianp\FuncCollection\Http\HttpParameterResolver;
use Mbrianp\FuncCollection\Http\ParamStack;
use Mbrianp\FuncCollection\Http\Request;
use Mbrianp\FuncCollection\Http\Response;
use Mbrianp\FuncCollection\Logic\AbstractController;
use Mbrianp\FuncCollection\ORM\ConnectionFactory;
use Mbrianp\FuncCollection\ORM\ConnectionParameters;
use Mbrianp\FuncCollection\ORM\EntityManager;
use Mbrianp\FuncCollection\ORM\ORM;
use Mbrianp\FuncCollection\ORM\ORMParameterResolver;
use Mbrianp\FuncCollection\Routing\Attribute\Route;
use Mbrianp\FuncCollection\Routing\Router;
use Mbrianp\FuncCollection\Routing\RouterParameterResolver;
use Mbrianp\FuncCollection\Routing\Routing;
use Mbrianp\FuncCollection\View\TemplateManager;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;

class Kernel
{
    /** @var array<int, string> */
    protected array $parametersResolvers = [
        HttpParameterResolver::class,
        RouterParameterResolver::class,
        ORMParameterResolver::class,
    ];

    protected const NESTED_ROUTES_SEPARATOR = '_';

    protected DIC $dependenciesContainer;

    public function __construct(protected array $config, protected array $registeredControllers = [])
    {
        $this->initContainer();
    }

    protected function initContainer(): void
    {
        $this->dependenciesContainer = new DIC();

        $get = new ParamStack($_GET);
        $post = new ParamStack($_POST);

        $request = new Service('http.request', Request::class, [$get, $post, $_SERVER['PATH_INFO'] ?? '/', $_SERVER['REQUEST_METHOD']]);
        $this->dependenciesContainer->addService($request);

        // EntityManager
        $ormService = new Service('db.orm', ORM::class, [$this->config['host'], $this->config['username'], $this->config['password'], $this->config['dbname'], $this->config['engine']]);
        $this->dependenciesContainer->addService($ormService);

        $orm = $this->dependenciesContainer->getService('db.orm')->getDriver();

        $entityManagerService = new Service('db.entity_manager', EntityManager::class, [$orm]);
        $this->dependenciesContainer->addService($entityManagerService);
    }

    /**
     * @return Router
     * @throws ReflectionException
     */
    protected function resolveRouterWithRoutes(): Router
    {
        $routes = [];

        foreach ($this->registeredControllers as $class) {
            if (class_exists($class)) {
                $prefix['name'] = null;
                $prefix['path'] = null;

                $rc = new ReflectionClass($class);

                if (1 == count($rc->getAttributes(Route::class))) {
                    /** @var Route $class_routeMetadata */
                    $class_routeMetadata = $rc->getAttributes(Route::class)[0]->newInstance();

                    $prefix['name'] = $class_routeMetadata->name;
                    $prefix['path'] = $class_routeMetadata->path;
                }

                foreach ($rc->getMethods() as $method) {
                    if (1 == count($method->getAttributes(Route::class))) {
                        /** @var Route $method_routeMetadata */
                        $method_routeMetadata = $method->getAttributes(Route::class)[0]->newInstance();

                        $method_routeMetadata->data['__controller'] = $rc->getName();
                        $method_routeMetadata->data['__method'] = $method->getName();

                        if ($prefix['name'] !== null)
                            $method_routeMetadata->name = $prefix['name'] . static::NESTED_ROUTES_SEPARATOR . $method_routeMetadata->name;

                        if ($prefix['path'] !== null)
                            $method_routeMetadata->path = $prefix['path'] . $method_routeMetadata->path;

                        $routes[] = $method_routeMetadata;
                    }
                }
            } else {
                throw new RuntimeException(\sprintf('Class %s does not exist', $class));
            }
        }

        return new Router($routes);
    }

    /**
     * @param array<int, ReflectionParameter> $parameters
     * @return array
     */
    protected function resolveParams(array $parameters): array
    {
        $resolvedParameters = [];

        foreach ($parameters as $parameter) {
            foreach ($this->parametersResolvers as $parameterResolver) {
                /**
                 * @var ParameterResolver $resolver
                 */
                $resolver = new $parameterResolver($this->dependenciesContainer);


                if ($resolver->supports($parameter)) {
                    $resolvedParameters[] = $resolver->resolve();
                }

                continue 1;
            }
        }

        return $resolvedParameters;
    }

    public function deployApp(Request $request): void
    {
        $router = $this->resolveRouterWithRoutes();

        if (!$router->hasRoutes()) {
            (new Response('If you are seeing this is because no route has been configured yet.', 200))->send();

            return;
        }

        $route = $router->resolveCurrentRoute($request);

        if (null == $route) {
            $templateManager = new TemplateManager(__DIR__ . '/templates');
            (new Response($templateManager->render('NotFound.html.php', ['path' => $request->path]), 404))->send();

            return;
        }

        $routingService = new Service('kernel.routing', Routing::class, [$route, $router->routes]);
        $this->dependenciesContainer->addService($routingService);

        ['__controller' => $controller, '__method' => $method] = $route->data;

        $rm = new ReflectionMethod($controller, $method);
        $params = $this->resolveParams($rm->getParameters());
        $constructorParams = [];

        if (\in_array(AbstractController::class, \class_parents($controller))) {
            $constructorParams[] = $this->config['templates_dir'];
        }

        $controller = new $controller(...$constructorParams);
        $response = $controller->$method(...$params);

        if (!$response instanceof Response) {
            throw new \LogicException(\sprintf('Invalid data returned from %s::%s must be of type %s, %s given', $controller::class, $method, Response::class, get_debug_type($response)));
        }

        $response->send();
    }
}