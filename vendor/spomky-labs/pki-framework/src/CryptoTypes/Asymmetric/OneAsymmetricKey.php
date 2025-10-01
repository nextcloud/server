<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric;

use LogicException;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\ECPublicKeyAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\Attribute\OneAsymmetricKeyAttributes;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\EC\ECPrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve25519\Ed25519PrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve25519\X25519PrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve448\Ed448PrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve448\X448PrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSAPrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSASSAPSSPrivateKey;
use UnexpectedValueException;
use function in_array;

/**
 * Implements PKCS #8 PrivateKeyInfo / OneAsymmetricKey ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5208#section-5
 * @see https://tools.ietf.org/html/rfc5958#section-2
 */
class OneAsymmetricKey
{
    /**
     * Version number for PrivateKeyInfo.
     *
     * @var int
     */
    final public const VERSION_1 = 0;

    /**
     * Version number for OneAsymmetricKey.
     *
     * @var int
     */
    final public const VERSION_2 = 1;

    /**
     * Version number.
     */
    private int $version;

    /**
     * @param AlgorithmIdentifierType $algo Algorithm
     * @param string $privateKeyData Private key data
     * @param null|OneAsymmetricKeyAttributes $attributes Optional attributes
     * @param null|BitString $publicKeyData Optional public key
     */
    protected function __construct(
        protected AlgorithmIdentifierType $algo,
        protected string $privateKeyData,
        protected ?OneAsymmetricKeyAttributes $attributes = null,
        protected ?BitString $publicKeyData = null,
        ?int $version = null
    ) {
        $this->version = $version ?? self::VERSION_2;
    }

    public static function create(
        AlgorithmIdentifierType $algo,
        string $privateKeyData,
        ?OneAsymmetricKeyAttributes $attributes = null,
        ?BitString $publicKeyData = null
    ): self {
        return new self($algo, $privateKeyData, $attributes, $publicKeyData);
    }

    /**
     * Get self with version number.
     */
    public function withVersion(int $version): self
    {
        $obj = clone $this;
        $obj->version = $version;
        return $obj;
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): static
    {
        $version = $seq->at(0)
            ->asInteger()
            ->intNumber();
        if (! in_array($version, [self::VERSION_1, self::VERSION_2], true)) {
            throw new UnexpectedValueException("Version {$version} not supported.");
        }
        $algo = AlgorithmIdentifier::fromASN1($seq->at(1)->asSequence());
        $key = $seq->at(2)
            ->asOctetString()
            ->string();
        $attribs = null;
        if ($seq->hasTagged(0)) {
            $attribs = OneAsymmetricKeyAttributes::fromASN1($seq->getTagged(0)
                ->asImplicit(Element::TYPE_SET)->asSet());
        }
        $pubkey = null;
        if ($seq->hasTagged(1)) {
            $pubkey = $seq->getTagged(1)
                ->asImplicit(Element::TYPE_BIT_STRING)->asBitString();
        }
        return static::create($algo, $key, $attribs, $pubkey)->withVersion($version);
    }

    /**
     * Initialize from DER data.
     */
    public static function fromDER(string $data): static
    {
        return static::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
    }

    /**
     * Initialize from a `PrivateKey`.
     *
     * Note that `OneAsymmetricKey` <-> `PrivateKey` conversions may not be bidirectional with all key types, since
     * `OneAsymmetricKey` may include attributes as well the public key that are not conveyed in a specific `PrivateKey`
     * object.
     */
    public static function fromPrivateKey(PrivateKey $private_key): static
    {
        return static::create($private_key->algorithmIdentifier(), $private_key->privateKeyData());
    }

    /**
     * Initialize from PEM.
     */
    public static function fromPEM(PEM $pem): self
    {
        return match ($pem->type()) {
            PEM::TYPE_PRIVATE_KEY => self::fromDER($pem->data()),
            PEM::TYPE_RSA_PRIVATE_KEY => self::fromPrivateKey(RSAPrivateKey::fromDER($pem->data())),
            PEM::TYPE_EC_PRIVATE_KEY => self::fromPrivateKey(ECPrivateKey::fromDER($pem->data())),
            default => throw new UnexpectedValueException('Invalid PEM type.'),
        };
    }

    /**
     * Get version number.
     */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * Get algorithm identifier.
     */
    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return $this->algo;
    }

    /**
     * Get private key data.
     */
    public function privateKeyData(): string
    {
        return $this->privateKeyData;
    }

    /**
     * Get private key.
     */
    public function privateKey(): PrivateKey
    {
        $algo = $this->algorithmIdentifier();
        switch ($algo->oid()) {
            // RSA
            case AlgorithmIdentifier::OID_RSA_ENCRYPTION:
                return RSAPrivateKey::fromDER($this->privateKeyData);
                // RSASSA-PSS
            case AlgorithmIdentifier::OID_RSASSA_PSS_ENCRYPTION:
                return RSASSAPSSPrivateKey::fromDER($this->privateKeyData);
                // elliptic curve
            case AlgorithmIdentifier::OID_EC_PUBLIC_KEY:
                $pk = ECPrivateKey::fromDER($this->privateKeyData);
                // NOTE: OpenSSL strips named curve from ECPrivateKey structure
                // when serializing into PrivateKeyInfo. However, RFC 5915 dictates
                // that parameters (NamedCurve) must always be included.
                // If private key doesn't encode named curve, assign from parameters.
                if (! $pk->hasNamedCurve()) {
                    if (! $algo instanceof ECPublicKeyAlgorithmIdentifier) {
                        throw new UnexpectedValueException('Not an EC algorithm.');
                    }
                    $pk = $pk->withNamedCurve($algo->namedCurve());
                }
                return $pk;
                // Ed25519
            case AlgorithmIdentifier::OID_ED25519:
                $pubkey = $this->publicKeyData?->string();
                // RFC 8410 defines `CurvePrivateKey ::= OCTET STRING` that
                // is encoded into private key data. So Ed25519 private key
                // is doubly wrapped into octet string encodings.
                return Ed25519PrivateKey::fromOctetString(OctetString::fromDER($this->privateKeyData), $pubkey)
                    ->withVersion($this->version)
                    ->withAttributes($this->attributes);
                // X25519
            case AlgorithmIdentifier::OID_X25519:
                $pubkey = $this->publicKeyData?->string();
                return X25519PrivateKey::fromOctetString(OctetString::fromDER($this->privateKeyData), $pubkey)
                    ->withVersion($this->version)
                    ->withAttributes($this->attributes);
                // Ed448
            case AlgorithmIdentifier::OID_ED448:
                $pubkey = $this->publicKeyData?->string();
                return Ed448PrivateKey::fromOctetString(OctetString::fromDER($this->privateKeyData), $pubkey)
                    ->withVersion($this->version)
                    ->withAttributes($this->attributes);
                // X448
            case AlgorithmIdentifier::OID_X448:
                $pubkey = $this->publicKeyData?->string();
                return X448PrivateKey::fromOctetString(OctetString::fromDER($this->privateKeyData), $pubkey)
                    ->withVersion($this->version)
                    ->withAttributes($this->attributes);
            default:
                throw new RuntimeException('Private key ' . $algo->name() . ' not supported.');
        }
    }

    /**
     * Get public key info corresponding to the private key.
     */
    public function publicKeyInfo(): PublicKeyInfo
    {
        // if public key is explicitly defined
        if ($this->hasPublicKeyData()) {
            return PublicKeyInfo::create($this->algo, $this->publicKeyData);
        }
        // else derive from private key
        return $this->privateKey()
            ->publicKey()
            ->publicKeyInfo();
    }

    /**
     * Whether attributes are present.
     */
    public function hasAttributes(): bool
    {
        return isset($this->attributes);
    }

    public function attributes(): OneAsymmetricKeyAttributes
    {
        if (! $this->hasAttributes()) {
            throw new LogicException('Attributes not set.');
        }
        return $this->attributes;
    }

    /**
     * Whether explicit public key data is present.
     */
    public function hasPublicKeyData(): bool
    {
        return isset($this->publicKeyData);
    }

    /**
     * Get the explicit public key data.
     */
    public function publicKeyData(): BitString
    {
        if (! $this->hasPublicKeyData()) {
            throw new LogicException('No explicit public key.');
        }
        return $this->publicKeyData;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [
            Integer::create($this->version),
            $this->algo->toASN1(),
            OctetString::create($this->privateKeyData),
        ];
        if ($this->attributes !== null) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->attributes->toASN1());
        }
        if ($this->publicKeyData !== null) {
            $elements[] = ImplicitlyTaggedType::create(1, $this->publicKeyData);
        }
        return Sequence::create(...$elements);
    }

    /**
     * Generate DER encoding.
     */
    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    /**
     * Generate PEM.
     */
    public function toPEM(): PEM
    {
        return PEM::create(PEM::TYPE_PRIVATE_KEY, $this->toDER());
    }
}
