<?php

namespace Mbrianp\FuncCollection\ORM;

use LogicException;
use Mbrianp\FuncCollection\ORM\Attributes\Column;
use Mbrianp\FuncCollection\ORM\Drivers\DatabaseDriverInterface;

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
            if (isset($column->options['filled_value'])) {
                $attribute = $column->options['filled_value'];
                $filledValues = \array_map(fn (string $column_): ?string => ResultFormatter::resolveRealSQLValue($entity->$column_, $column), $attribute->columns);

                $pattern = $attribute->pattern ?? \str_repeat('%s ', \count($filledValues));
                $value = \sprintf(\trim($pattern), ...$filledValues);

                $values[$column->name] = $value;

                continue;
            }

            $values[$column->name] = ResultFormatter::resolveRealSQLValue($entity->{$column->name}, $column);
        }

        return $this->driver->insert($schema->table->name, $values);
    }
}