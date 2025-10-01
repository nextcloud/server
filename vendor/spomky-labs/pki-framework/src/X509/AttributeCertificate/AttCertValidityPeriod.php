<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use DateTimeImmutable;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\GeneralizedTime;
use SpomkyLabs\Pki\X509\Feature\DateTimeHelper;

/**
 * Implements *AttCertValidityPeriod* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 */
final class AttCertValidityPeriod
{
    use DateTimeHelper;

    private function __construct(
        private readonly DateTimeImmutable $notBeforeTime,
        private readonly DateTimeImmutable $notAfterTime
    ) {
    }

    public static function create(DateTimeImmutable $notBeforeTime, DateTimeImmutable $notAfterTime): self
    {
        return new self($notBeforeTime, $notAfterTime);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $nb = $seq->at(0)
            ->asGeneralizedTime()
            ->dateTime();
        $na = $seq->at(1)
            ->asGeneralizedTime()
            ->dateTime();
        return self::create($nb, $na);
    }

    /**
     * Initialize from date strings.
     *
     * @param null|string $nb_date Not before date
     * @param null|string $na_date Not after date
     * @param null|string $tz Timezone string
     */
    public static function fromStrings(?string $nb_date, ?string $na_date, ?string $tz = null): self
    {
        $nb = self::createDateTime($nb_date, $tz);
        $na = self::createDateTime($na_date, $tz);
        return self::create($nb, $na);
    }

    /**
     * Get not before time.
     */
    public function notBeforeTime(): DateTimeImmutable
    {
        return $this->notBeforeTime;
    }

    /**
     * Get not after time.
     */
    public function notAfterTime(): DateTimeImmutable
    {
        return $this->notAfterTime;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(
            GeneralizedTime::create($this->notBeforeTime),
            GeneralizedTime::create($this->notAfterTime)
        );
    }
}
