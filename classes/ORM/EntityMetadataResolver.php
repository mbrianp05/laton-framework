<?php

namespace Mbrianp\FuncCollection\ORM;

use Mbrianp\FuncCollection\ORM\Attributes\Id;
use Mbrianp\FuncCollection\ORM\Attributes\Repository;
use Mbrianp\FuncCollection\ORM\Attributes\Column;
use Mbrianp\FuncCollection\ORM\Attributes\Table;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * This class obtains some useful metadata from Entity attributes (related to
 * the ORM) applied into its properties.
 */
class EntityMetadataResolver
{
    public function __construct(protected string|object $entity)
    {
    }

    public function getIdProperty(): ?ReflectionProperty
    {
        $reflectionClass = new ReflectionClass($this->entity);

        foreach ($reflectionClass->getProperties() as $property) {
            if (1 <= count($property->getAttributes(Id::class))) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Gets the repository class of an entity
     * If it does not have one, returns null
     */
    public function getRepositoryClass(): ?string
    {
        $reflectionClass = new ReflectionClass($this->entity);
        $repositoryAttributes = $reflectionClass->getAttributes(Repository::class);

        if (1 <= count($repositoryAttributes)) {
            return $repositoryAttributes[0]->newInstance()->class;
        }

        return null;
    }

    /**
     * Gets the schema of some entity
     * The schema is the columns metadata
     * and the table metadata.
     */
    public function getSchema(): Schema
    {
        $table = $this->getTable();
        $columns = $this->getColumns();

        return new Schema($table, $columns);
    }

    public function getColumns(): array
    {
        $reflectionClass = new ReflectionClass($this->entity);
        $columns = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $columnAttribute = $this->getColumnAttribute($property->getName());
            $idAttributes = $property->getAttributes(Id::class);

            if (null == $columnAttribute) {
                continue;
            }

            if (1 <= count($idAttributes)) {
                $columnAttribute->options['AUTO_INCREMENTS'] = true;
                $columnAttribute->options['PRIMARY_KEY'] = true;
            }

            $columns[] = $columnAttribute;
        }

        return $columns;
    }

    /**
     * Resolves the data that was not given in the attributes
     * For example:
     *      #[Column]
     *      public string $name;
     *
     * In that case was not specified the type of the column,
     * but the property is of type string so, the column will be
     * of type string, if the type is specified in the attribute
     * this value will have more priority, so if they're both defined
     * the value from the attribute will be the one that will be taken.
     */
    protected function resolveColumnMetadata(Column $column, ReflectionProperty $property): Column
    {
        if (null == $column->name) {
            $column->name = Utils::resolveValidIdentifier($property->getName());
        }

        if (null == $column->type) {
            $column->type = 'string';

            if (!$property->getType() instanceof ReflectionUnionType) {
                $column->type = $property->getType()->getName();
            }
        }

        $column->options['property'] = $property->getName();

        return $column;
    }

    public function getTableName(): string
    {
        return $this->getTable()->name;
    }

    public function getTable(): Table
    {
        $reflectionClass = new ReflectionClass($this->entity);
        $tableAttributes = $reflectionClass->getAttributes(Table::class);

        if (1 <= count($tableAttributes)) {
            $table = $tableAttributes[0]->newInstance();
        } else {
            $table = new Table(Utils::resolveTableName($reflectionClass->getShortName()));
        }

        return $table;
    }

    public function getAttributes(string $property, string $attribute = null): array
    {
        $reflectionClass = new ReflectionClass($this->entity);

        return $reflectionClass->getProperty($property)->getAttributes($attribute);
    }

    public function getColumnAttribute(string $property): ?Column
    {
        $columnAttributes = $this->getAttributes($property, Column::class);

        if (1 <= \count($columnAttributes)) {
            $attr = $columnAttributes[0];
            $attrInstance = $attr->newInstance();

            return $this->resolveColumnMetadata($attrInstance, new ReflectionProperty($this->entity, $property));
        }

        return null;
    }
}