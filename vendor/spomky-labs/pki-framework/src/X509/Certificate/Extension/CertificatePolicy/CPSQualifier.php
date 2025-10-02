<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\IA5String;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements *CPSuri* ASN.1 type used by 'Certificate Policies' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.4
 */
final class CPSQualifier extends PolicyQualifierInfo
{
    private function __construct(
        private readonly string $uri
    ) {
        parent::__construct(self::OID_CPS);
    }

    public static function create(string $uri): self
    {
        return new self($uri);
    }

    /**
     * @return self
     */
    public static function fromQualifierASN1(UnspecifiedType $el): PolicyQualifierInfo
    {
        return self::create($el->asString()->string());
    }

    public function uri(): string
    {
        return $this->uri;
    }

    protected function qualifierASN1(): Element
    {
        return IA5String::create($this->uri);
    }
}
