<?php

namespace Mbrianp\FuncCollection\ORM;

use LogicException;
use Mbrianp\FuncCollection\ORM\Drivers\DatabaseDriverInterface;
use Mbrianp\FuncCollection\ORM\Type\ORMTypeInterface;
use ReflectionAttribute;

class EntityManager
{
    public function __construct(protected DatabaseDriverInterface $driver)
    {
    }

    public function getRepository(string $entityClass): AbstractRepository
    {
        $metadataResolver = new EntityMetadataResolver($entityClass);
        $repository = $metadataResolver->getRepositoryClass();

        if (null === $repository) {
            throw new LogicException(\sprintf('No repository was set for entity: %s', $entityClass));
        }

        if (!\in_array(AbstractRepository::class, \class_parents($repository))) {
            throw new LogicException(\sprintf('Repository classes must extend from %s', AbstractRepository::class));
        }

        return new $repository($this->driver);
    }

    public function persist(object $entity): bool
    {
        $metadataResolver = new EntityMetadataResolver($entity);
        $schema = $metadataResolver->getSchema();

        $columns = $schema->columns;
        $values = [];

        foreach ($columns as $column) {
            $valueResolvers = ORM::getValueResolvers();
            $types = ORM::getTypes();

            $attributes = $metadataResolver->getAttributes($column->options['property']);

            $valueResolvers = \array_filter($attributes, fn(ReflectionAttribute $attr): bool => \in_array($attr->getName(), $valueResolvers));
            $value = null;

            foreach ($valueResolvers as $resolver) {
                if (!Utils::classImplements($resolver->name, ValueResolverInterface::class)) {
                    throw new LogicException(\sprintf('Value resolver %s must implement %s', $resolver->name, ValueResolver::class));
                }

                $resolver = $resolver->newInstance();
                $value = $resolver->resolve($values);
            }

            // This means that the value has no resolvers
            // SO the var keeps being null
            if (null === $value) {
                $value = $entity->{$column->options['property']};
            }

            if (\array_key_exists($column->type, $types)) {
                Utils::checkType($types[$column->type]);

                $type = new $types[$column->type]();
                $value = $type->resolveToSQL($value);
            }

            $values[$column->options['property']] = $value;
        }

        return $this->driver->insert($schema->table->name, $values);
    }
}