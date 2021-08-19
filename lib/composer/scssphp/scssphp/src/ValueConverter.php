<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

use ScssPhp\ScssPhp\Node\Number;

final class ValueConverter
{
    // Prevent instantiating it
    private function __construct()
    {
    }

    /**
     * Parses a value from a Scss source string.
     *
     * The returned value is guaranteed to be supported by the
     * Compiler methods for registering custom variables. No other
     * guarantee about it is provided. It should be considered
     * opaque values by the caller.
     *
     * @param string $source
     *
     * @return mixed
     */
    public static function parseValue($source)
    {
        $parser = new Parser(__CLASS__);

        if (!$parser->parseValue($source, $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid value source "%s".', $source));
        }

        return $value;
    }

    /**
     * Converts a PHP value to a Sass value
     *
     * The returned value is guaranteed to be supported by the
     * Compiler methods for registering custom variables. No other
     * guarantee about it is provided. It should be considered
     * opaque values by the caller.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function fromPhp($value)
    {
        if ($value instanceof Number) {
            return $value;
        }

        if (is_array($value) && isset($value[0]) && \in_array($value[0], [Type::T_NULL, Type::T_COLOR, Type::T_KEYWORD, Type::T_LIST, Type::T_MAP, Type::T_STRING])) {
            return $value;
        }

        if ($value === null) {
            return Compiler::$null;
        }

        if ($value === true) {
            return Compiler::$true;
        }

        if ($value === false) {
            return Compiler::$false;
        }

        if ($value === '') {
            return Compiler::$emptyString;
        }

        if (\is_int($value) || \is_float($value)) {
            return new Number($value, '');
        }

        if (\is_string($value)) {
            return [Type::T_STRING, '"', [$value]];
        }

        throw new \InvalidArgumentException(sprintf('Cannot convert the value of type "%s" to a Sass value.', gettype($value)));
    }
}
