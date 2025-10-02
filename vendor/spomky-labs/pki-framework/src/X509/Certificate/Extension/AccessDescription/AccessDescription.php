<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\AccessDescription;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;

/**
 * Base class implementing *AccessDescription* ASN.1 type for 'Authority Information Access' and 'Subject Information
 * Access' certificate extensions.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.2.1
 */
abstract class AccessDescription
{
    /**
     * @param string $accessMethod Access method OID
     * @param GeneralName $accessLocation Access location
     */
    protected function __construct(
        protected readonly string $accessMethod,
        protected readonly GeneralName $accessLocation
    ) {
    }

    /**
     * Initialize from ASN.1.
     */
    abstract public static function fromASN1(Sequence $seq): static;

    /**
     * Get the access method OID.
     */
    public function accessMethod(): string
    {
        return $this->accessMethod;
    }

    /**
     * Get the access location.
     */
    public function accessLocation(): GeneralName
    {
        return $this->accessLocation;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(ObjectIdentifier::create($this->accessMethod), $this->accessLocation->toASN1());
    }
}
