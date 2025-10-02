<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements 'Inhibit anyPolicy' extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.14
 */
final class InhibitAnyPolicyExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly int $skipCerts
    ) {
        parent::__construct(self::OID_INHIBIT_ANY_POLICY, $critical);
    }

    public static function create(bool $critical, int $skipCerts): self
    {
        return new self($critical, $skipCerts);
    }

    public function skipCerts(): int
    {
        return $this->skipCerts;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        return self::create($critical, UnspecifiedType::fromDER($data)->asInteger()->intNumber());
    }

    protected function valueASN1(): Element
    {
        return Integer::create($this->skipCerts);
    }
}
