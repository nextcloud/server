<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *NoticeReference* ASN.1 type used by 'Certificate Policies' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.4
 */
final class NoticeReference
{
    /**
     * Notification reference numbers.
     *
     * @var int[]
     */
    private readonly array $numbers;

    private function __construct(
        private readonly DisplayText $organization,
        int ...$numbers
    ) {
        $this->numbers = $numbers;
    }

    public static function create(DisplayText $organization, int ...$numbers): self
    {
        return new self($organization, ...$numbers);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $org = DisplayText::fromASN1($seq->at(0)->asString());
        $numbers = array_map(
            static fn (UnspecifiedType $el) => $el->asInteger()
                ->intNumber(),
            $seq->at(1)
                ->asSequence()
                ->elements()
        );
        return self::create($org, ...$numbers);
    }

    /**
     * Get reference organization.
     */
    public function organization(): DisplayText
    {
        return $this->organization;
    }

    /**
     * Get reference numbers.
     *
     * @return int[]
     */
    public function numbers(): array
    {
        return $this->numbers;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $org = $this->organization->toASN1();
        $nums = array_map(static fn ($number) => Integer::create($number), $this->numbers);
        return Sequence::create($org, Sequence::create(...$nums));
    }
}
