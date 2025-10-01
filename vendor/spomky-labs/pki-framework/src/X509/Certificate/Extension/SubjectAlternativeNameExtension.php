<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;

/**
 * Implements 'Subject Alternative Name' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.6
 */
final class SubjectAlternativeNameExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly GeneralNames $names
    ) {
        parent::__construct(self::OID_SUBJECT_ALT_NAME, $critical);
    }

    public static function create(bool $critical, GeneralNames $names): self
    {
        return new self($critical, $names);
    }

    public function names(): GeneralNames
    {
        return $this->names;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        return self::create($critical, GeneralNames::fromASN1(UnspecifiedType::fromDER($data)->asSequence()));
    }

    protected function valueASN1(): Element
    {
        return $this->names->toASN1();
    }
}
