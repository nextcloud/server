<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Type;

use Ramsey\Uuid\Exception\InvalidArgumentException;

use function ctype_digit;
use function ltrim;
use function strpos;
use function substr;

/**
 * A value object representing an integer
 *
 * This class exists for type-safety purposes, to ensure that integers
 * returned from ramsey/uuid methods as strings are truly integers and not some
 * other kind of string.
 *
 * To support large integers beyond PHP_INT_MAX and PHP_INT_MIN on both 64-bit
 * and 32-bit systems, we store the integers as strings.
 *
 * @psalm-immutable
 */
final class Integer implements NumberInterface
{
    /**
     * @psalm-var numeric-string
     */
    private $value;

    /**
     * @var bool
     */
    private $isNegative = false;

    /**
     * @param mixed $value The integer value to store
     */
    public function __construct($value)
    {
        $value = (string) $value;
        $sign = '+';

        // If the value contains a sign, remove it for ctype_digit() check.
        if (strpos($value, '-') === 0 || strpos($value, '+') === 0) {
            $sign = substr($value, 0, 1);
            $value = substr($value, 1);
        }

        if (!ctype_digit($value)) {
            throw new InvalidArgumentException(
                'Value must be a signed integer or a string containing only '
                . 'digits 0-9 and, optionally, a sign (+ or -)'
            );
        }

        // Trim any leading zeros.
        $value = ltrim($value, '0');

        // Set to zero if the string is empty after trimming zeros.
        if ($value === '') {
            $value = '0';
        }

        // Add the negative sign back to the value.
        if ($sign === '-' && $value !== '0') {
            $value = $sign . $value;
            $this->isNegative = true;
        }

        /** @psalm-var numeric-string $numericValue */
        $numericValue = $value;

        $this->value = $numericValue;
    }

    public function isNegative(): bool
    {
        return $this->isNegative;
    }

    /**
     * @psalm-return numeric-string
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    public function serialize(): string
    {
        return $this->toString();
    }

    /**
     * Constructs the object from a serialized string representation
     *
     * @param string $serialized The serialized string representation of the object
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @psalm-suppress UnusedMethodCall
     */
    public function unserialize($serialized): void
    {
        $this->__construct($serialized);
    }
}
