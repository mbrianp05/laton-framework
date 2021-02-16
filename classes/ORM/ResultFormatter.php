<?php

namespace Mbrianp\FuncCollection\ORM;

use Mbrianp\FuncCollection\ORM\Attributes\Column;
use RuntimeException;

/**
 * This class will transform an array with values from database
 * and will convert strings from database to the specified data type
 * in the attributes applied in the properties of the entity
 * in an entity with those values in the proper properties likes this:
 *
 *      // This array comes from a result from database
 *      [
 *          "name" => "Brian",
 *          "lastname" => "Monteagudo Perez"
 *      ]
 *
 *      // Now it becomes something like:
 *      object(User) {
 *          name: String = "Brian",
 *          lastname: String = "Monteagudo Perez"
 *      }
 *
 *      DB:
 *          "{"roles": ["USER", "ADMIN"]}" // String
 *
 *      Formatter:
 *           ['roles' => ['USER', 'ADMIN']]
 */
class ResultFormatter
{
    public function __construct(protected string $entity)
    {
    }

    /**
     * Due to SQL does not accept some types like json
     * This will convert a string with the json in it
     * to a PHP array.
     *
     * @param string $valueFromDatabase
     * @param Column $column
     * @return string|array|object
     */
    public static function resolveRealPHPValue(string $valueFromDatabase, Column $column): string|array|object|int
    {
        return match ($column->type) {
            null, 'string' => $valueFromDatabase,
            'integer' => (int) $valueFromDatabase,
            'json' => \json_decode($valueFromDatabase),
            default => $valueFromDatabase,
        };
    }

    public static function resolveRealSQLValue(string|array|int|null $PHPvalue, Column $column): string|int|null
    {
        return match ($column->type) {
            'json' => \json_encode($PHPvalue),
            'string' => $PHPvalue,
            'integer' => (int) $PHPvalue,
            default => $PHPvalue,
        };
    }

    protected function formatSingleResult(array $result): object
    {
        $entityInstance = new $this->entity();
        $entityMetadata = new EntityMetadataResolver($entityInstance);

        foreach ($result as $property => $value) {
            if (!\is_string($property)) {
                throw new RuntimeException(\sprintf('Cannot assign the value %s to an unknown property', $value));
            }

            $property = \array_filter(\array_keys(\get_class_vars($entityInstance::class)), fn(string $property_): bool => \strtolower($property_) == $property);

            if (1 >= count($property)) {
                $property = $property[\array_key_first($property)];
            }

            $entityInstance->$property = static::resolveRealPHPValue($value, $entityMetadata->getColumnAttributeOf($property));
        }

        return $entityInstance;
    }

    /**
     * $values parameter can be several results (multidimensional array)
     * or just a result and depends of that will be returned an object if
     * it's only a result or an array<object> if they are more than one result.
     */
    public function format(array $results): object|array
    {
        if (empty($results)) {
            return $results;
        }

        $firstElement = $results[\array_key_first($results)];
        $resolved = null;
        $oneResult = false;

        if (\is_string($firstElement)) {
            $results = [$results];
            $oneResult = true;
        }

        foreach ($results as $result) {
            $resolved[] = $this->formatSingleResult($result);
        }

        if ($oneResult) {
            return $resolved[0];
        }

        return $resolved;
    }
}