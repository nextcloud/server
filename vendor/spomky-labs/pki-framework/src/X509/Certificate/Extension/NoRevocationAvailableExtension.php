<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;

/**
 * Implements 'No Revocation Available' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.3.6
 */
final class NoRevocationAvailableExtension extends Extension
{
    private function __construct(bool $critical)
    {
        parent::__construct(self::OID_NO_REV_AVAIL, $critical);
    }

    public static function create(bool $critical): self
    {
        return new self($critical);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        NullType::fromDER($data);
        return self::create($critical);
    }

    protected function valueASN1(): Element
    {
        return NullType::create();
    }
}
