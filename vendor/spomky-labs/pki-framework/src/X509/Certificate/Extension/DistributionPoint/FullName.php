<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use SpomkyLabs\Pki\X509\GeneralName\UniformResourceIdentifier;

/**
 * Implements 'fullName' ASN.1 CHOICE type of *DistributionPointName* used by 'CRL Distribution Points' certificate
 * extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
final class FullName extends DistributionPointName
{
    private function __construct(
        private readonly GeneralNames $names
    ) {
        parent::__construct(self::TAG_FULL_NAME);
    }

    public static function create(GeneralNames $names): self
    {
        return new self($names);
    }

    /**
     * Initialize with a single URI.
     */
    public static function fromURI(string $uri): self
    {
        return self::create(GeneralNames::create(UniformResourceIdentifier::create($uri)));
    }

    public function names(): GeneralNames
    {
        return $this->names;
    }

    protected function _valueASN1(): Element
    {
        return $this->names->toASN1();
    }
}
