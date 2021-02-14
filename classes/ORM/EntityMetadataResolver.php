<?php

namespace Mbrianp\FuncCollection\ORM;

use Mbrianp\FuncCollection\ORM\Attributes\Id;
use Mbrianp\FuncCollection\ORM\Attributes\Repository;
use Mbrianp\FuncCollection\ORM\Attributes\Column;
use Mbrianp\FuncCollection\ORM\Attributes\Table;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

class EntityMetadataResolver
{
    public function __construct(protected string|object $class)
    {
    }

    public function getRepositoryClass(): ?string
    {
        $reflectionClass = new ReflectionClass($this->class);
        $repositoryAttributes = $reflectionClass->getAttributes(Repository::class);

        if (1 <= count($repositoryAttributes)) {
            return $repositoryAttributes[0]->newInstance()->class;
        }

        return null;
    }

    public function getSchema(): Schema
    {
        $reflectionClass = new ReflectionClass($this->class);
        $tableAttributes = $reflectionClass->getAttributes(Table::class);

        if (1 <= count($tableAttributes)) {
            $table = $tableAttributes[0]->newInstance();
        } else {
            $table = new Table(Utils::resolveTableName($reflectionClass->getShortName()));
        }

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

        $column->type = str_replace('string', 'VARCHAR', $column->type);

        return $column;
    }
}