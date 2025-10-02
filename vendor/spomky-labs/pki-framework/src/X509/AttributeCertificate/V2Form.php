<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\X501\ASN1\Name;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;

/**
 * Implements *V2Form* ASN.1 type used as a attribute certificate issuer.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 */
final class V2Form extends AttCertIssuer
{
    private function __construct(
        private readonly ?GeneralNames $issuerName,
        private readonly ?IssuerSerial $baseCertificateID,
        private readonly ?ObjectDigestInfo $objectDigestInfo
    ) {
    }

    public static function create(
        ?GeneralNames $issuerName = null,
        ?IssuerSerial $baseCertificateID = null,
        ?ObjectDigestInfo $objectDigestInfo = null
    ): self {
        return new self($issuerName, $baseCertificateID, $objectDigestInfo);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromV2ASN1(Sequence $seq): self
    {
        $issuer = null;
        $cert_id = null;
        $digest_info = null;
        if ($seq->has(0, Element::TYPE_SEQUENCE)) {
            $issuer = GeneralNames::fromASN1($seq->at(0)->asSequence());
        }
        if ($seq->hasTagged(0)) {
            $cert_id = IssuerSerial::fromASN1(
                $seq->getTagged(0)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        if ($seq->hasTagged(1)) {
            $digest_info = ObjectDigestInfo::fromASN1(
                $seq->getTagged(1)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        return self::create($issuer, $cert_id, $digest_info);
    }

    /**
     * Check whether issuer name is set.
     */
    public function hasIssuerName(): bool
    {
        return isset($this->issuerName);
    }

    /**
     * Get issuer name.
     */
    public function issuerName(): GeneralNames
    {
        if (! $this->hasIssuerName()) {
            throw new LogicException('issuerName not set.');
        }
        return $this->issuerName;
    }

    /**
     * Get DN of the issuer.
     *
     * This is a convenience method conforming to RFC 5755, which states that Issuer must contain only one non-empty
     * distinguished name.
     */
    public function name(): Name
    {
        return $this->issuerName()
            ->firstDN();
    }

    public function toASN1(): Element
    {
        $elements = [];
        if (isset($this->issuerName)) {
            $elements[] = $this->issuerName->toASN1();
        }
        if (isset($this->baseCertificateID)) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->baseCertificateID->toASN1());
        }
        if (isset($this->objectDigestInfo)) {
            $elements[] = ImplicitlyTaggedType::create(1, $this->objectDigestInfo->toASN1());
        }
        return ImplicitlyTaggedType::create(0, Sequence::create(...$elements));
    }

    public function identifiesPKC(Certificate $cert): bool
    {
        $name = $this->issuerName?->firstDN();
        return ! ($name === null || ! $cert->tbsCertificate()->subject()->equals($name));
    }
}
