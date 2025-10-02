<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use UnexpectedValueException;

/**
 * Implements 'Authority Key Identifier' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.1
 */
final class AuthorityKeyIdentifierExtension extends Extension
{
    private function __construct(
        bool $critical,
        private readonly ?string $keyIdentifier,
        private readonly ?GeneralNames $authorityCertIssuer,
        private readonly null|string $authorityCertSerialNumber
    ) {
        parent::__construct(self::OID_AUTHORITY_KEY_IDENTIFIER, $critical);
    }

    /**
     * @param bool $critical Conforming CA's must mark as non-critical (false)
     * @param null|string $keyIdentifier Key identifier
     * @param null|GeneralNames $authorityCertIssuer Issuer name
     */
    public static function create(
        bool $critical,
        ?string $keyIdentifier,
        ?GeneralNames $authorityCertIssuer = null,
        null|string $authorityCertSerialNumber = null
    ): self {
        return new self($critical, $keyIdentifier, $authorityCertIssuer, $authorityCertSerialNumber);
    }

    /**
     * Create from public key info.
     */
    public static function fromPublicKeyInfo(PublicKeyInfo $pki): self
    {
        return self::create(false, $pki->keyIdentifier());
    }

    /**
     * Whether key identifier is present.
     */
    public function hasKeyIdentifier(): bool
    {
        return isset($this->keyIdentifier);
    }

    /**
     * Get key identifier.
     */
    public function keyIdentifier(): string
    {
        if (! $this->hasKeyIdentifier()) {
            throw new LogicException('keyIdentifier not set.');
        }
        return $this->keyIdentifier;
    }

    /**
     * Whether issuer is present.
     */
    public function hasIssuer(): bool
    {
        return isset($this->authorityCertIssuer);
    }

    public function issuer(): GeneralNames
    {
        if (! $this->hasIssuer()) {
            throw new LogicException('authorityCertIssuer not set.');
        }
        return $this->authorityCertIssuer;
    }

    /**
     * Whether serial is present.
     */
    public function hasSerial(): bool
    {
        return isset($this->authorityCertSerialNumber);
    }

    /**
     * Get serial number.
     *
     * @return string Base 10 integer string
     */
    public function serial(): string
    {
        if (! $this->hasSerial()) {
            throw new LogicException('authorityCertSerialNumber not set.');
        }
        return $this->authorityCertSerialNumber;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $seq = UnspecifiedType::fromDER($data)->asSequence();
        $keyIdentifier = null;
        $issuer = null;
        $serial = null;
        if ($seq->hasTagged(0)) {
            $keyIdentifier = $seq->getTagged(0)
                ->asImplicit(Element::TYPE_OCTET_STRING)
                ->asOctetString()
                ->string();
        }
        if ($seq->hasTagged(1) || $seq->hasTagged(2)) {
            if (! $seq->hasTagged(1) || ! $seq->hasTagged(2)) {
                throw new UnexpectedValueException(
                    'AuthorityKeyIdentifier must have both' .
                    ' authorityCertIssuer and authorityCertSerialNumber' .
                    ' present or both absent.'
                );
            }
            $issuer = GeneralNames::fromASN1($seq->getTagged(1)->asImplicit(Element::TYPE_SEQUENCE)->asSequence());
            $serial = $seq->getTagged(2)
                ->asImplicit(Element::TYPE_INTEGER)
                ->asInteger()
                ->number();
        }
        return self::create($critical, $keyIdentifier, $issuer, $serial);
    }

    protected function valueASN1(): Element
    {
        $elements = [];
        if (isset($this->keyIdentifier)) {
            $elements[] = ImplicitlyTaggedType::create(0, OctetString::create($this->keyIdentifier));
        }
        // if either issuer or serial is set, both must be set
        if (isset($this->authorityCertIssuer) ||
            isset($this->authorityCertSerialNumber)) {
            if (! isset($this->authorityCertIssuer,
                $this->authorityCertSerialNumber)) {
                throw new LogicException(
                    'AuthorityKeyIdentifier must have both' .
                    ' authorityCertIssuer and authorityCertSerialNumber' .
                    ' present or both absent.'
                );
            }
            $elements[] = ImplicitlyTaggedType::create(1, $this->authorityCertIssuer->toASN1());
            $elements[] = ImplicitlyTaggedType::create(2, Integer::create($this->authorityCertSerialNumber));
        }
        return Sequence::create(...$elements);
    }
}
