<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\PolicyMappings;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;

/**
 * Implements ASN.1 type containing policy mapping values to be used in 'Policy Mappings' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.5
 */
final class PolicyMapping
{
    /**
     * @param string $issuerDomainPolicy OID of the issuer policy
     * @param string $subjectDomainPolicy OID of the subject policy
     */
    private function __construct(
        private readonly string $issuerDomainPolicy,
        private readonly string $subjectDomainPolicy
    ) {
    }

    public static function create(string $issuerDomainPolicy, string $subjectDomainPolicy): self
    {
        return new self($issuerDomainPolicy, $subjectDomainPolicy);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $issuer_policy = $seq->at(0)
            ->asObjectIdentifier()
            ->oid();
        $subject_policy = $seq->at(1)
            ->asObjectIdentifier()
            ->oid();
        return self::create($issuer_policy, $subject_policy);
    }

    /**
     * Get issuer domain policy.
     *
     * @return string OID in dotted format
     */
    public function issuerDomainPolicy(): string
    {
        return $this->issuerDomainPolicy;
    }

    /**
     * Get subject domain policy.
     *
     * @return string OID in dotted format
     */
    public function subjectDomainPolicy(): string
    {
        return $this->subjectDomainPolicy;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(
            ObjectIdentifier::create($this->issuerDomainPolicy),
            ObjectIdentifier::create($this->subjectDomainPolicy)
        );
    }
}
