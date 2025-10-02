<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\RSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use UnexpectedValueException;

/**
 * Implements PKCS #1 RSAPublicKey ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc2437#section-11.1.1
 */
final class RSAPublicKey extends PublicKey
{
    private function __construct(
        private readonly string $modulus,
        private readonly string $publicExponent
    ) {
    }

    public static function create(string $modulus, string $publicExponent): self
    {
        return new self($modulus, $publicExponent);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $n = $seq->at(0)
            ->asInteger()
            ->number();
        $e = $seq->at(1)
            ->asInteger()
            ->number();
        return self::create($n, $e);
    }

    /**
     * Initialize from DER data.
     */
    public static function fromDER(string $data): self
    {
        return self::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
    }

    /**
     * @see PublicKey::fromPEM()
     */
    public static function fromPEM(PEM $pem): self
    {
        switch ($pem->type()) {
            case PEM::TYPE_RSA_PUBLIC_KEY:
                return self::fromDER($pem->data());
            case PEM::TYPE_PUBLIC_KEY:
                $pki = PublicKeyInfo::fromDER($pem->data());
                if ($pki->algorithmIdentifier()
                    ->oid() !==
                    AlgorithmIdentifier::OID_RSA_ENCRYPTION) {
                    throw new UnexpectedValueException('Not an RSA public key.');
                }
                return self::fromDER($pki->publicKeyData()->string());
        }
        throw new UnexpectedValueException('Invalid PEM type ' . $pem->type());
    }

    /**
     * Get modulus.
     *
     * @return string Base 10 integer
     */
    public function modulus(): string
    {
        return $this->modulus;
    }

    /**
     * Get public exponent.
     *
     * @return string Base 10 integer
     */
    public function publicExponent(): string
    {
        return $this->publicExponent;
    }

    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return RSAEncryptionAlgorithmIdentifier::create();
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(Integer::create($this->modulus), Integer::create($this->publicExponent));
    }

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
        return PEM::create(PEM::TYPE_RSA_PUBLIC_KEY, $this->toDER());
    }
}
