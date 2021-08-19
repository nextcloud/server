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

use Ramsey\Uuid\Exception\InvalidBytesException;
use Ramsey\Uuid\Uuid;

use function decbin;
use function str_pad;
use function strlen;
use function strpos;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * Provides common functionality for handling the variant, as defined by RFC 4122
 *
 * @psalm-immutable
 */
trait VariantTrait
{
    /**
     * Returns the bytes that comprise the fields
     */
    abstract public function getBytes(): string;

    /**
     * Returns the variant identifier, according to RFC 4122, for the given bytes
     *
     * The following values may be returned:
     *
     * - `0` -- Reserved, NCS backward compatibility.
     * - `2` -- The variant specified in RFC 4122.
     * - `6` -- Reserved, Microsoft Corporation backward compatibility.
     * - `7` -- Reserved for future definition.
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     *
     * @return int The variant identifier, according to RFC 4122
     */
    public function getVariant(): int
    {
        if (strlen($this->getBytes()) !== 16) {
            throw new InvalidBytesException('Invalid number of bytes');
        }

        /** @var array $parts */
        $parts = unpack('n*', $this->getBytes());

        // $parts[5] is a 16-bit, unsigned integer containing the variant bits
        // of the UUID. We convert this integer into a string containing a
        // binary representation, padded to 16 characters. We analyze the first
        // three characters (three most-significant bits) to determine the
        // variant.
        $binary = str_pad(
            decbin((int) $parts[5]),
            16,
            '0',
            STR_PAD_LEFT
        );

        $msb = substr($binary, 0, 3);

        if ($msb === '111') {
            $variant = Uuid::RESERVED_FUTURE;
        } elseif ($msb === '110') {
            $variant = Uuid::RESERVED_MICROSOFT;
        } elseif (strpos($msb, '10') === 0) {
            $variant = Uuid::RFC_4122;
        } else {
            $variant = Uuid::RESERVED_NCS;
        }

        return $variant;
    }
}
