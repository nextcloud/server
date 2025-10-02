<?php

declare(strict_types=1);

namespace JsonSchema\Constraints\TypeCheck;

class StrictTypeCheck implements TypeCheckInterface
{
    public static function isObject($value)
    {
        return is_object($value);
    }

    public static function isArray($value)
    {
        return is_array($value);
    }

    public static function propertyGet($value, $property)
    {
        return $value->{$property};
    }

    public static function propertySet(&$value, $property, $data)
    {
        $value->{$property} = $data;
    }

    public static function propertyExists($value, $property)
    {
        return property_exists($value, $property);
    }

    public static function propertyCount($value)
    {
        if (!is_object($value)) {
            return 0;
        }

        return count(get_object_vars($value));
    }
}
