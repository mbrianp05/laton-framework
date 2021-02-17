<?php

namespace Mbrianp\FuncCollection\ORM;

use App\Entity\User;
use LogicException;
use Mbrianp\FuncCollection\DIC\DIC;
use Mbrianp\FuncCollection\Kernel\ParameterResolver;
use Mbrianp\FuncCollection\Routing\Attribute\Route;
use ReflectionParameter;

class ORMParameterResolver implements ParameterResolver
{
    protected const ENTITY_NAMESPACE = 'App\Entity\\';
    protected string $target = 'entity_manager';
    protected ReflectionParameter $parameter;

    public function __construct(protected DIC $dependenciesContainer)
    {
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $this->parameter = $parameter;

        if (EntityManager::class == $parameter->getType()->getName()) {
            return true;
        }

        if (!\in_array($parameter->getType()->getName(), ['string', 'int', 'array', 'bool', 'float']) && \class_exists($parameter->getType()->getName())
            && \in_array(
                AbstractRepository::class,
                \class_parents($parameter->getType()->getName())
            )
        ) {
            $this->target = 'repository';

            return true;
        }

        if (\str_starts_with($this->parameter->getType()->getName(), static::ENTITY_NAMESPACE)) {
            $this->target = 'entity';

            if (!$this->parameter->getType()->allowsNull()) {
                throw new LogicException(\sprintf('Parameter %s must accept null in case that no result will be found', $this->parameter->getType()->getName()));
            }

            return true;
        }

        return false;
    }

    public function resolve(): object
    {
        $type = $this->parameter->getType();
        $class = $type->getName();

        /**
         * @var Route $currentRoute
         */
        $currentRoute = $this->dependenciesContainer->getService('kernel.routing')->getCurrentRoute();
        $criteria = [];

        foreach ($currentRoute->parameters as $parameter => $value) {
            if (\property_exists($class, $parameter)) {
                $criteria[$parameter] = $value;

                break;
            }
        }

        /**
         * @var EntityManager $em
         */
        $em = $this->dependenciesContainer->getService('db.entity_manager');

        return match ($this->target) {
            'entity_manager' => $em,
            'repository' => $em->getRepository($class::getRefEntity()),
            'entity' => $em->getRepository($class)->findOneBy($criteria),
        };
    }
}