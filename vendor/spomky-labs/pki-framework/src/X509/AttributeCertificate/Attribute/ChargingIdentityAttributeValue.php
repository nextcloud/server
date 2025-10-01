<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate\Attribute;

/**
 * Implements value for 'Charging Identity' attribute.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.4.3
 */
final class ChargingIdentityAttributeValue extends IetfAttrSyntax
{
    final public const OID = '1.3.6.1.5.5.7.10.3';

    public static function create(IetfAttrValue ...$values): self
    {
        return new self(self::OID, ...$values);
    }
}
