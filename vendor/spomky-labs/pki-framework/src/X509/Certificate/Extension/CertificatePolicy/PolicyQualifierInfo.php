<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;

/**
 * Base class for *PolicyQualifierInfo* ASN.1 types used by 'Certificate Policies' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.4
 */
abstract class PolicyQualifierInfo
{
    /**
     * OID for the CPS Pointer qualifier.
     *
     * @var string
     */
    public const OID_CPS = '1.3.6.1.5.5.7.2.1';

    /**
     * OID for the user notice qualifier.
     *
     * @var string
     */
    public const OID_UNOTICE = '1.3.6.1.5.5.7.2.2';

    protected function __construct(
        protected string $oid
    ) {
    }

    /**
     * Initialize from qualifier ASN.1 element.
     */
    abstract public static function fromQualifierASN1(UnspecifiedType $el): self;

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $oid = $seq->at(0)
            ->asObjectIdentifier()
            ->oid();
        return match ($oid) {
            self::OID_CPS => CPSQualifier::fromQualifierASN1($seq->at(1)),
            self::OID_UNOTICE => UserNoticeQualifier::fromQualifierASN1($seq->at(1)),
            default => throw new UnexpectedValueException("Qualifier {$oid} not supported."),
        };
    }

    /**
     * Get qualifier identifier.
     */
    public function oid(): string
    {
        return $this->oid;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(ObjectIdentifier::create($this->oid), $this->qualifierASN1());
    }

    /**
     * Generate ASN.1 for the 'qualifier' field.
     */
    abstract protected function qualifierASN1(): Element;
}
