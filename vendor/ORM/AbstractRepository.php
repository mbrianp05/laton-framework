<?php

namespace Mbrianp\FuncCollection\ORM;

use LogicException;
use Mbrianp\FuncCollection\ORM\Attributes\OneToMany;
use Mbrianp\FuncCollection\ORM\Drivers\DatabaseDriverInterface;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractRepository
{
    private string $entity;
    private EntityMetadataResolver $metadataResolver;

    public function __construct(private DatabaseDriverInterface $driver, private array $mapping = [])
    {
        $this->entity = static::getRefEntity();
        $this->metadataResolver = new EntityMetadataResolver($this->entity);
    }

    abstract public static function getRefEntity(): string;

    private function formatResults(array $results): object|array
    {
        $formatter = new ResultFormatter($this->entity, $this->mapping);
        $formattedResults = $formatter->format($results);
        return $this->relateResults($formattedResults);
    }

    public function find(int $id): ?object
    {
        $idProperty = $this->metadataResolver->getIdProperty();

        if (null == $idProperty) {
            throw new LogicException(\sprintf('Cannot find a registry from database without defining a column as ID in %s class', $this->entity));
        }

        $propertyName = $idProperty->getName();
        $results = $this->driver->select($this->metadataResolver->getTableName())->where($propertyName, $id)->limit(1)->getOneOrNullResult();

        return $this->formatResults($results);
    }

    /**
     * @param array $criteria
     * @param int|null $limit
     * @param array $orderBy
     * @return array
     *
     * Example:
     * // Find all users named Brian
     * findBy(['name' => 'Brian'], orderBy: ['id' => 'DESC']);
     */
    public function findBy(array $criteria, int $limit = null, array $orderBy = []): array
    {
        $query = $this->driver->select($this->metadataResolver->getTableName(), null);

        // Will check if where was already called
        $where = false;

        foreach ($criteria as $column => $value) {
            if (!$where) {
                $query->where($column, $value);

                // Prevent that orWhere is activated
                continue;
            }

            $query->orWhere($column, $value);
        }

        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }

        if (null !== $limit) {
            $query->limit($limit);
        }

        return $this->formatResults($query->getResults());
    }

    public function findOneBy(array $criteria, array $orderBy = []): ?object
    {
        return $this->findBy($criteria, 1, $orderBy)[0] ?? null;
    }

    public function findAll(array $orderBy = [], int $limit = null): array
    {
        $query = $this->driver->select($this->metadataResolver->getTableName());
        $query->orderBy($orderBy);

        if (null !== $limit)
            $query->limit($limit);

        $results = $query->getResults();
        $results = $this->formatResults($results);

        return $results;
    }

    protected function relateResults(array $results): array
    {
        $relatedProperties = $this->metadataResolver->getRelationColumns(new ReflectionClass($this->entity));
        $relatedPropertyNames = \array_map(fn (ReflectionProperty $prop): string => $prop->getName(), $relatedProperties);
        $relations = \array_map(fn (ReflectionProperty $prop): object => $this->metadataResolver->getRelationAttribute($prop)->newInstance(), $relatedProperties);
        $relations = \array_combine($relatedPropertyNames, $relations);

        foreach ($results as $result) {
            foreach ($relations as $property => $relation) {
                if (\array_key_exists($property, $this->mapping)) {
                    continue;
                }

                $reflection = new ReflectionClass($this->entity);
                $property = $reflection->getProperty($property);
                $relationAttr = $this->metadataResolver->getRelationAttribute($property)->newInstance();


                if (\in_array($property->getName(), $this->mapping)) {
                    continue;
                }

                $relatedEntity = $relationAttr->targetEntity;
                $mappedBy = $relationAttr->mappedBy ?? $this->metadataResolver->getTableName() . '_id';


                $emr = new EntityMetadataResolver($relatedEntity);
                $repository = $emr->getRepositoryClass();

                if (null == $repository) {
                    throw new LogicException(\sprintf('Entity %s does not have configured a repository', $relatedEntity));
                }

                $repository = new $repository($this->driver, [$mappedBy => $result]);
                $repositoryResults = $repository->findBy([$mappedBy => $result->id]);

                $result->{$property->getName()} = $repositoryResults;
            }
        }

        return $results;
    }
}