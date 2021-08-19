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

use Ramsey\Uuid\Fields\FieldsInterface as BaseFieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;

/**
 * RFC 4122 defines fields for a specific variant of UUID
 *
 * The fields of an RFC 4122 variant UUID are:
 *
 * * **time_low**: The low field of the timestamp, an unsigned 32-bit integer
 * * **time_mid**: The middle field of the timestamp, an unsigned 16-bit integer
 * * **time_hi_and_version**: The high field of the timestamp multiplexed with
 *   the version number, an unsigned 16-bit integer
 * * **clock_seq_hi_and_reserved**: The high field of the clock sequence
 *   multiplexed with the variant, an unsigned 8-bit integer
 * * **clock_seq_low**: The low field of the clock sequence, an unsigned
 *   8-bit integer
 * * **node**: The spatially unique node identifier, an unsigned 48-bit
 *   integer
 *
 * @link http://tools.ietf.org/html/rfc4122#section-4.1 RFC 4122, § 4.1: Format
 *
 * @psalm-immutable
 */
interface FieldsInterface extends BaseFieldsInterface
{
    /**
     * Returns the full 16-bit clock sequence, with the variant bits (two most
     * significant bits) masked out
     */
    public function getClockSeq(): Hexadecimal;

    /**
     * Returns the high field of the clock sequence multiplexed with the variant
     */
    public function getClockSeqHiAndReserved(): Hexadecimal;

    /**
     * Returns the low field of the clock sequence
     */
    public function getClockSeqLow(): Hexadecimal;

    /**
     * Returns the node field
     */
    public function getNode(): Hexadecimal;

    /**
     * Returns the high field of the timestamp multiplexed with the version
     */
    public function getTimeHiAndVersion(): Hexadecimal;

    /**
     * Returns the low field of the timestamp
     */
    public function getTimeLow(): Hexadecimal;

    /**
     * Returns the middle field of the timestamp
     */
    public function getTimeMid(): Hexadecimal;

    /**
     * Returns the full 60-bit timestamp, without the version
     */
    public function getTimestamp(): Hexadecimal;

    /**
     * Returns the variant
     *
     * The variant number describes the layout of the UUID. The variant
     * number has the following meaning:
     *
     * - 0 - Reserved for NCS backward compatibility
     * - 2 - The RFC 4122 variant
     * - 6 - Reserved, Microsoft Corporation backward compatibility
     * - 7 - Reserved for future definition
     *
     * For RFC 4122 variant UUIDs, this value should always be the integer `2`.
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     */
    public function getVariant(): int;

    /**
     * Returns the version
     *
     * The version number describes how the UUID was generated and has the
     * following meaning:
     *
     * 1. Time-based UUID
     * 2. DCE security UUID
     * 3. Name-based UUID hashed with MD5
     * 4. Randomly generated UUID
     * 5. Name-based UUID hashed with SHA-1
     *
     * This returns `null` if the UUID is not an RFC 4122 variant, since version
     * is only meaningful for this variant.
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public function getVersion(): ?int;

    /**
     * Returns true if these fields represent a nil UUID
     *
     * The nil UUID is special form of UUID that is specified to have all 128
     * bits set to zero.
     */
    public function isNil(): bool;
}
