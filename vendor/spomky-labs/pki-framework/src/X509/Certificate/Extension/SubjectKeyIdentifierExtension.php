<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements 'Subject Key Identifier' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.2
 */
final class SubjectKeyIdentifierExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly string $keyIdentifier
    ) {
        parent::__construct(self::OID_SUBJECT_KEY_IDENTIFIER, $critical);
    }

    public static function create(bool $critical, string $keyIdentifier): self
    {
        return new self($critical, $keyIdentifier);
    }

    /**
     * Get key identifier.
     */
    public function keyIdentifier(): string
    {
        return $this->keyIdentifier;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        return self::create($critical, UnspecifiedType::fromDER($data)->asOctetString()->string());
    }

    protected function valueASN1(): Element
    {
        return OctetString::create($this->keyIdentifier);
    }
}
