<?php

declare(strict_types=1);

namespace JsonSchema\Constraints\TypeCheck;

class LooseTypeCheck implements TypeCheckInterface
{
    public static function isObject($value)
    {
        return
            is_object($value) ||
            (is_array($value) && (count($value) == 0 || self::isAssociativeArray($value)));
    }

    public static function isArray($value)
    {
        return
            is_array($value) &&
            (count($value) == 0 || !self::isAssociativeArray($value));
    }

    public static function propertyGet($value, $property)
    {
        if (is_object($value)) {
            return $value->{$property};
        }

        return $value[$property];
    }

    public static function propertySet(&$value, $property, $data)
    {
        if (is_object($value)) {
            $value->{$property} = $data;
        } else {
            $value[$property] = $data;
        }
    }

    public static function propertyExists($value, $property)
    {
        if (is_object($value)) {
            return property_exists($value, $property);
        }

        return is_array($value) && array_key_exists($property, $value);
    }

    public static function propertyCount($value)
    {
        if (is_object($value)) {
            return count(get_object_vars($value));
        }

        return count($value);
    }

    /**
     * Check if the provided array is associative or not
     *
     * @param array $arr
     *
     * @return bool
     */
    private static function isAssociativeArray($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
