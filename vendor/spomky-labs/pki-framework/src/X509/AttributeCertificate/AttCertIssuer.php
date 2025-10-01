<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Name;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\GeneralName\DirectoryName;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use UnexpectedValueException;

/**
 * Base class implementing *AttCertIssuer* ASN.1 CHOICE type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 */
abstract class AttCertIssuer
{
    /**
     * Generate ASN.1 element.
     */
    abstract public function toASN1(): Element;

    /**
     * Check whether AttCertIssuer identifies given certificate.
     */
    abstract public function identifiesPKC(Certificate $cert): bool;

    /**
     * Initialize from distinguished name.
     *
     * This conforms to RFC 5755 which states that only v2Form must be used, and issuerName must contain exactly one
     * GeneralName of DirectoryName type.
     *
     * @see https://tools.ietf.org/html/rfc5755#section-4.2.3
     */
    public static function fromName(Name $name): self
    {
        return V2Form::create(GeneralNames::create(DirectoryName::create($name)));
    }

    /**
     * Initialize from an issuer's public key certificate.
     */
    public static function fromPKC(Certificate $cert): self
    {
        return self::fromName($cert->tbsCertificate()->subject());
    }

    /**
     * Initialize from ASN.1.
     *
     * @param UnspecifiedType $el CHOICE
     */
    public static function fromASN1(UnspecifiedType $el): self
    {
        if (! $el->isTagged()) {
            throw new UnexpectedValueException('v1Form issuer not supported.');
        }
        $tagged = $el->asTagged();
        return match ($tagged->tag()) {
            0 => V2Form::fromV2ASN1($tagged->asImplicit(Element::TYPE_SEQUENCE)->asSequence()),
            default => throw new UnexpectedValueException('Unsupported issuer type.'),
        };
    }
}
