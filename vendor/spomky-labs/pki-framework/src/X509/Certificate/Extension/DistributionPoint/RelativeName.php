<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\X501\ASN1\RDN;

/**
 * Implements 'nameRelativeToCRLIssuer' ASN.1 CHOICE type of *DistributionPointName* used by 'CRL Distribution Points'
 * certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
final class RelativeName extends DistributionPointName
{
    private function __construct(
        private readonly RDN $rdn
    ) {
        parent::__construct(self::TAG_RDN);
    }

    public static function create(RDN $rdn): self
    {
        return new self($rdn);
    }

    public function rdn(): RDN
    {
        return $this->rdn;
    }

    protected function _valueASN1(): Element
    {
        return $this->rdn->toASN1();
    }
}
