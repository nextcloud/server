<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\AccessDescription;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;

/**
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.2.1
 */
final class AuthorityAccessDescription extends AccessDescription
{
    /**
     * Access method OID's.
     *
     * @var string
     */
    final public const OID_METHOD_OSCP = '1.3.6.1.5.5.7.48.1';

    final public const OID_METHOD_CA_ISSUERS = '1.3.6.1.5.5.7.48.2';

    public static function create(string $accessMethod, GeneralName $accessLocation): self
    {
        return new self($accessMethod, $accessLocation);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): static
    {
        return new static($seq->at(0)->asObjectIdentifier()->oid(), GeneralName::fromASN1($seq->at(1)->asTagged()));
    }

    /**
     * Check whether access method is OSCP.
     */
    public function isOSCPMethod(): bool
    {
        return $this->accessMethod === self::OID_METHOD_OSCP;
    }

    /**
     * Check whether access method is CA issuers.
     */
    public function isCAIssuersMethod(): bool
    {
        return $this->accessMethod === self::OID_METHOD_CA_ISSUERS;
    }
}
