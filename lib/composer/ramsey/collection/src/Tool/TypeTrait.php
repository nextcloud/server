<?php

/**
 * This file is part of the ramsey/collection library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Collection\Tool;

use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;

/**
 * Provides functionality to check values for specific types.
 */
trait TypeTrait
{
    /**
     * Returns `true` if value is of the specified type.
     *
     * @param string $type The type to check the value against.
     * @param mixed $value The value to check.
     */
    protected function checkType(string $type, $value): bool
    {
        switch ($type) {
            case 'array':
                return is_array($value);
            case 'bool':
            case 'boolean':
                return is_bool($value);
            case 'callable':
                return is_callable($value);
            case 'float':
            case 'double':
                return is_float($value);
            case 'int':
            case 'integer':
                return is_int($value);
            case 'null':
                return $value === null;
            case 'numeric':
                return is_numeric($value);
            case 'object':
                return is_object($value);
            case 'resource':
                return is_resource($value);
            case 'scalar':
                return is_scalar($value);
            case 'string':
                return is_string($value);
            case 'mixed':
                return true;
            default:
                return $value instanceof $type;
        }
    }
}
