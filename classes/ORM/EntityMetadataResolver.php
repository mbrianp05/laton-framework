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
    public function __construct(protected string|object $class)
    {
    }

    public function getIdProperty(): ?ReflectionProperty
    {
        $reflectionClass = new ReflectionClass($this->class);

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
        $reflectionClass = new ReflectionClass($this->class);
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
        $reflectionClass = new ReflectionClass($this->class);
        $table = $this->getTable();

        $columns = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(Column::class);
            $idAttributes = $property->getAttributes(Id::class);

            if (0 == count($columnAttributes)) {
                continue;
            }

            /**
             * @var Column $columnAttribute
             */
            $columnAttribute = $columnAttributes[0]->newInstance();

            if (1 <= count($idAttributes)) {
                $columnAttribute->options['AUTO_INCREMENTS'] = true;
                $columnAttribute->options['PRIMARY_KEY'] = true;
            }

            $columns[] = $this->resolveColumnMetadata($columnAttribute, $property);
        }

        return new Schema($table, $columns);
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

        return $column;
    }

    public function getTableName(): string
    {
        return $this->getTable()->name;
    }

    public function getTable(): Table
    {
        $reflectionClass = new ReflectionClass($this->class);
        $tableAttributes = $reflectionClass->getAttributes(Table::class);

        if (1 <= count($tableAttributes)) {
            $table = $tableAttributes[0]->newInstance();
        } else {
            $table = new Table(Utils::resolveTableName($reflectionClass->getShortName()));
        }

        return $table;
    }
}