<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate;

use DateTimeImmutable;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\GeneralizedTime;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UTCTime;
use SpomkyLabs\Pki\ASN1\Type\TimeType;
use SpomkyLabs\Pki\X509\Feature\DateTimeHelper;
use UnexpectedValueException;

/**
 * Implements *Time* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.1
 */
final class Time
{
    use DateTimeHelper;

    /**
     * Time ASN.1 type tag.
     */
    private readonly int $type;

    private function __construct(
        protected DateTimeImmutable $dt,
        ?int $type
    ) {
        $this->type = $type ?? self::determineType($dt);
    }

    public static function create(DateTimeImmutable $dt): self
    {
        return new self($dt, null);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(TimeType $el): self
    {
        return self::create($el->dateTime());
    }

    /**
     * Initialize from date string.
     */
    public static function fromString(?string $time, ?string $tz = null): self
    {
        return self::create(self::createDateTime($time, $tz));
    }

    public function dateTime(): DateTimeImmutable
    {
        return $this->dt;
    }

    /**
     * Generate ASN.1.
     */
    public function toASN1(): TimeType
    {
        $dt = $this->dt;
        switch ($this->type) {
            case Element::TYPE_UTC_TIME:
                return UTCTime::create($dt);
            case Element::TYPE_GENERALIZED_TIME:
                // GeneralizedTime must not contain fractional seconds
                // (rfc5280 4.1.2.5.2)
                if ((int) $dt->format('u') !== 0) {
                    // remove fractional seconds (round down)
                    $dt = self::roundDownFractionalSeconds($dt);
                }
                return GeneralizedTime::create($dt);
        }
        throw new UnexpectedValueException('Time type ' . Element::tagToName($this->type) . ' not supported.');
    }

    /**
     * Determine whether to use UTCTime or GeneralizedTime ASN.1 type.
     *
     * @return int Type tag
     */
    protected static function determineType(DateTimeImmutable $dt): int
    {
        if ($dt->format('Y') >= 2050) {
            return Element::TYPE_GENERALIZED_TIME;
        }
        return Element::TYPE_UTC_TIME;
    }
}
