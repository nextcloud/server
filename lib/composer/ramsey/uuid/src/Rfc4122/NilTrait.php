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

namespace Ramsey\Uuid\Rfc4122;

/**
 * Provides common functionality for nil UUIDs
 *
 * The nil UUID is special form of UUID that is specified to have all 128 bits
 * set to zero.
 *
 * @link https://tools.ietf.org/html/rfc4122#section-4.1.7 RFC 4122, § 4.1.7: Nil UUID
 *
 * @psalm-immutable
 */
trait NilTrait
{
    /**
     * Returns the bytes that comprise the fields
     */
    abstract public function getBytes(): string;

    /**
     * Returns true if the byte string represents a nil UUID
     */
    public function isNil(): bool
    {
        return $this->getBytes() === "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    }
}
