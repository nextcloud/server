<?php

declare(strict_types=1);

namespace JsonSchema\Tool;

class DeepComparer
{
    /**
     * @param mixed $left
     * @param mixed $right
     */
    public static function isEqual($left, $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        $isLeftScalar = is_scalar($left);
        $isLeftNumber = is_int($left) || is_float($left);
        $isRightScalar = is_scalar($right);
        $isRightNumber = is_int($right) || is_float($right);

        if ($isLeftScalar && $isRightScalar) {
            /*
             * In Json-Schema mathematically equal numbers are compared equal
             */
            if ($isLeftNumber && $isRightNumber && (float) $left === (float) $right) {
                return true;
            }

            return $left === $right;
        }

        if ($isLeftScalar !== $isRightScalar) {
            return false;
        }

        if (is_array($left) && is_array($right)) {
            return self::isArrayEqual($left, $right);
        }

        if ($left instanceof \stdClass && $right instanceof \stdClass) {
            return self::isArrayEqual((array) $left, (array) $right);
        }

        return false;
    }

    /**
     * @param array<string|int, mixed> $left
     * @param array<string|int, mixed> $right
     */
    private static function isArrayEqual(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $key => $value) {
            if (!array_key_exists($key, $right)) {
                return false;
            }

            if (!self::isEqual($value, $right[$key])) {
                return false;
            }
        }

        return true;
    }
}
