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

use function is_numeric;

/**
 * A value object representing a decimal
 *
 * This class exists for type-safety purposes, to ensure that decimals
 * returned from ramsey/uuid methods as strings are truly decimals and not some
 * other kind of string.
 *
 * To support values as true decimals and not as floats or doubles, we store the
 * decimals as strings.
 *
 * @psalm-immutable
 */
final class Decimal implements NumberInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var bool
     */
    private $isNegative = false;

    /**
     * @param mixed $value The decimal value to store
     */
    public function __construct($value)
    {
        $value = (string) $value;

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Value must be a signed decimal or a string containing only '
                . 'digits 0-9 and, optionally, a decimal point or sign (+ or -)'
            );
        }

        // Remove the leading +-symbol.
        if (strpos($value, '+') === 0) {
            $value = substr($value, 1);
        }

        // For cases like `-0` or `-0.0000`, convert the value to `0`.
        if (abs((float) $value) === 0.0) {
            $value = '0';
        }

        if (strpos($value, '-') === 0) {
            $this->isNegative = true;
        }

        $this->value = $value;
    }

    public function isNegative(): bool
    {
        return $this->isNegative;
    }

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
