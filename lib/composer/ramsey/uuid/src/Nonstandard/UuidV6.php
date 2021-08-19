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

namespace Ramsey\Uuid\Nonstandard;

use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Exception\DateTimeException;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Rfc4122\FieldsInterface as Rfc4122FieldsInterface;
use Ramsey\Uuid\Rfc4122\UuidInterface;
use Ramsey\Uuid\Rfc4122\UuidV1;
use Ramsey\Uuid\Uuid;
use Throwable;

use function hex2bin;
use function str_pad;
use function substr;

use const STR_PAD_LEFT;

/**
 * Ordered-time, or version 6, UUIDs include timestamp, clock sequence, and node
 * values that are combined into a 128-bit unsigned integer
 *
 * @link https://github.com/uuid6/uuid6-ietf-draft UUID version 6 IETF draft
 * @link http://gh.peabody.io/uuidv6/ "Version 6" UUIDs
 *
 * @psalm-immutable
 */
final class UuidV6 extends Uuid implements UuidInterface
{
    /**
     * Creates a version 6 (time-based) UUID
     *
     * @param Rfc4122FieldsInterface $fields The fields from which to construct a UUID
     * @param NumberConverterInterface $numberConverter The number converter to use
     *     for converting hex values to/from integers
     * @param CodecInterface $codec The codec to use when encoding or decoding
     *     UUID strings
     * @param TimeConverterInterface $timeConverter The time converter to use
     *     for converting timestamps extracted from a UUID to unix timestamps
     */
    public function __construct(
        Rfc4122FieldsInterface $fields,
        NumberConverterInterface $numberConverter,
        CodecInterface $codec,
        TimeConverterInterface $timeConverter
    ) {
        if ($fields->getVersion() !== Uuid::UUID_TYPE_PEABODY) {
            throw new InvalidArgumentException(
                'Fields used to create a UuidV6 must represent a '
                . 'version 6 (ordered-time) UUID'
            );
        }

        parent::__construct($fields, $numberConverter, $codec, $timeConverter);
    }

    /**
     * Returns a DateTimeInterface object representing the timestamp associated
     * with the UUID
     *
     * @return DateTimeImmutable A PHP DateTimeImmutable instance representing
     *     the timestamp of a version 6 UUID
     */
    public function getDateTime(): DateTimeInterface
    {
        $time = $this->timeConverter->convertTime($this->fields->getTimestamp());

        try {
            return new DateTimeImmutable(
                '@'
                . $time->getSeconds()->toString()
                . '.'
                . str_pad($time->getMicroseconds()->toString(), 6, '0', STR_PAD_LEFT)
            );
        } catch (Throwable $e) {
            throw new DateTimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Converts this UUID into an instance of a version 1 UUID
     */
    public function toUuidV1(): UuidV1
    {
        $hex = $this->getHex()->toString();
        $hex = substr($hex, 7, 5)
            . substr($hex, 13, 3)
            . substr($hex, 3, 4)
            . '1' . substr($hex, 0, 3)
            . substr($hex, 16);

        /** @var LazyUuidFromString $uuid */
        $uuid = Uuid::fromBytes((string) hex2bin($hex));

        return $uuid->toUuidV1();
    }

    /**
     * Converts a version 1 UUID into an instance of a version 6 UUID
     */
    public static function fromUuidV1(UuidV1 $uuidV1): UuidV6
    {
        $hex = $uuidV1->getHex()->toString();
        $hex = substr($hex, 13, 3)
            . substr($hex, 8, 4)
            . substr($hex, 0, 5)
            . '6' . substr($hex, 5, 3)
            . substr($hex, 16);

        /** @var LazyUuidFromString $uuid */
        $uuid = Uuid::fromBytes((string) hex2bin($hex));

        return $uuid->toUuidV6();
    }
}
