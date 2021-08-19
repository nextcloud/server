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

use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Fields\SerializableFieldsTrait;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;

use function bin2hex;
use function dechex;
use function hexdec;
use function sprintf;
use function str_pad;
use function strlen;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * RFC 4122 variant UUIDs are comprised of a set of named fields
 *
 * Internally, this class represents the fields together as a 16-byte binary
 * string.
 *
 * @psalm-immutable
 */
final class Fields implements FieldsInterface
{
    use NilTrait;
    use SerializableFieldsTrait;
    use VariantTrait;
    use VersionTrait;

    /**
     * @var string
     */
    private $bytes;

    /**
     * @param string $bytes A 16-byte binary string representation of a UUID
     *
     * @throws InvalidArgumentException if the byte string is not exactly 16 bytes
     * @throws InvalidArgumentException if the byte string does not represent an RFC 4122 UUID
     * @throws InvalidArgumentException if the byte string does not contain a valid version
     */
    public function __construct(string $bytes)
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException(
                'The byte string must be 16 bytes long; '
                . 'received ' . strlen($bytes) . ' bytes'
            );
        }

        $this->bytes = $bytes;

        if (!$this->isCorrectVariant()) {
            throw new InvalidArgumentException(
                'The byte string received does not conform to the RFC 4122 variant'
            );
        }

        if (!$this->isCorrectVersion()) {
            throw new InvalidArgumentException(
                'The byte string received does not contain a valid RFC 4122 version'
            );
        }
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }

    public function getClockSeq(): Hexadecimal
    {
        $clockSeq = hexdec(bin2hex(substr($this->bytes, 8, 2))) & 0x3fff;

        return new Hexadecimal(str_pad(dechex($clockSeq), 4, '0', STR_PAD_LEFT));
    }

    public function getClockSeqHiAndReserved(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 8, 1)));
    }

    public function getClockSeqLow(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 9, 1)));
    }

    public function getNode(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 10)));
    }

    public function getTimeHiAndVersion(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 6, 2)));
    }

    public function getTimeLow(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 0, 4)));
    }

    public function getTimeMid(): Hexadecimal
    {
        return new Hexadecimal(bin2hex(substr($this->bytes, 4, 2)));
    }

    /**
     * Returns the full 60-bit timestamp, without the version
     *
     * For version 2 UUIDs, the time_low field is the local identifier and
     * should not be returned as part of the time. For this reason, we set the
     * bottom 32 bits of the timestamp to 0's. As a result, there is some loss
     * of fidelity of the timestamp, for version 2 UUIDs. The timestamp can be
     * off by a range of 0 to 429.4967295 seconds (or 7 minutes, 9 seconds, and
     * 496730 microseconds).
     *
     * For version 6 UUIDs, the timestamp order is reversed from the typical RFC
     * 4122 order (the time bits are in the correct bit order, so that it is
     * monotonically increasing). In returning the timestamp value, we put the
     * bits in the order: time_low + time_mid + time_hi.
     */
    public function getTimestamp(): Hexadecimal
    {
        switch ($this->getVersion()) {
            case Uuid::UUID_TYPE_DCE_SECURITY:
                $timestamp = sprintf(
                    '%03x%04s%08s',
                    hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff,
                    $this->getTimeMid()->toString(),
                    ''
                );

                break;
            case Uuid::UUID_TYPE_PEABODY:
                $timestamp = sprintf(
                    '%08s%04s%03x',
                    $this->getTimeLow()->toString(),
                    $this->getTimeMid()->toString(),
                    hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff
                );

                break;
            default:
                $timestamp = sprintf(
                    '%03x%04s%08s',
                    hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff,
                    $this->getTimeMid()->toString(),
                    $this->getTimeLow()->toString()
                );
        }

        return new Hexadecimal($timestamp);
    }

    public function getVersion(): ?int
    {
        if ($this->isNil()) {
            return null;
        }

        /** @var array $parts */
        $parts = unpack('n*', $this->bytes);

        return (int) $parts[4] >> 12;
    }

    private function isCorrectVariant(): bool
    {
        if ($this->isNil()) {
            return true;
        }

        return $this->getVariant() === Uuid::RFC_4122;
    }
}
