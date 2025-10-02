<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint\DistributionPoint;

/**
 * Implements 'Freshest CRL' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.15
 */
final class FreshestCRLExtension extends CRLDistributionPointsExtension
{
    public static function create(bool $critical, DistributionPoint ...$distribution_points): self
    {
        return new self(self::OID_FRESHEST_CRL, $critical, ...$distribution_points);
    }
}
