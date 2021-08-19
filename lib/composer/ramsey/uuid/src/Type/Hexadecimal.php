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

use function ctype_xdigit;
use function strpos;
use function strtolower;
use function substr;

/**
 * A value object representing a hexadecimal number
 *
 * This class exists for type-safety purposes, to ensure that hexadecimal numbers
 * returned from ramsey/uuid methods as strings are truly hexadecimal and not some
 * other kind of string.
 *
 * @psalm-immutable
 */
final class Hexadecimal implements TypeInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value The hexadecimal value to store
     */
    public function __construct(string $value)
    {
        $value = strtolower($value);

        if (strpos($value, '0x') === 0) {
            $value = substr($value, 2);
        }

        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Value must be a hexadecimal number'
            );
        }

        $this->value = $value;
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
