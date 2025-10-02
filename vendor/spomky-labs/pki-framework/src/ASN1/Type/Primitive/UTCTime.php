<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use DateTimeImmutable;
use DateTimeZone;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\BaseTime;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;

/**
 * Implements *UTCTime* type.
 */
final class UTCTime extends BaseTime
{
    use UniversalClass;
    use PrimitiveType;

    /**
     * Regular expression to parse date.
     *
     * DER restricts format to UTC timezone (Z suffix).
     *
     * @var string
     */
    final public const REGEX = '#^' .
    '(\d\d)' . // YY
    '(\d\d)' . // MM
    '(\d\d)' . // DD
    '(\d\d)' . // hh
    '(\d\d)' . // mm
    '(\d\d)' . // ss
    'Z' . // TZ
    '$#';

    private function __construct(DateTimeImmutable $dt)
    {
        parent::__construct(self::TYPE_UTC_TIME, $dt);
    }

    public static function create(DateTimeImmutable $dt): self
    {
        return new self($dt);
    }

    public static function fromString(string $time): static
    {
        return new static(new DateTimeImmutable($time, new DateTimeZone('UTC')));
    }

    protected function encodedAsDER(): string
    {
        $dt = $this->dateTime->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('ymdHis\\Z');
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        $length = Length::expectFromDER($data, $idx)->intLength();
        $str = mb_substr($data, $idx, $length, '8bit');
        $idx += $length;
        if (preg_match(self::REGEX, $str, $match) !== 1) {
            throw new DecodeException('Invalid UTCTime format.');
        }
        [, $year, $month, $day, $hour, $minute, $second] = $match;
        $time = $year . $month . $day . $hour . $minute . $second . self::TZ_UTC;
        $dt = DateTimeImmutable::createFromFormat('!ymdHisT', $time, new DateTimeZone('UTC'));
        if ($dt === false) {
            throw new DecodeException('Failed to decode UTCTime');
        }
        $offset = $idx;
        return self::create($dt);
    }
}
