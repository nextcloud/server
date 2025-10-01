<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric;

use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSAPublicKey;
use UnexpectedValueException;

/**
 * Base class for public keys.
 */
abstract class PublicKey
{
    /**
     * Get the public key algorithm identifier.
     */
    abstract public function algorithmIdentifier(): AlgorithmIdentifierType;

    /**
     * Get DER encoding of the public key.
     */
    abstract public function toDER(): string;

    /**
     * Get the public key data for subjectPublicKey in PublicKeyInfo.
     */
    public function subjectPublicKey(): BitString
    {
        return BitString::create($this->toDER());
    }

    /**
     * Get the public key as a PublicKeyInfo type.
     */
    public function publicKeyInfo(): PublicKeyInfo
    {
        return PublicKeyInfo::fromPublicKey($this);
    }

    /**
     * Initialize public key from PEM.
     *
     * @return PublicKey
     */
    public static function fromPEM(PEM $pem)
    {
        return match ($pem->type()) {
            PEM::TYPE_RSA_PUBLIC_KEY => RSAPublicKey::fromDER($pem->data()),
            PEM::TYPE_PUBLIC_KEY => PublicKeyInfo::fromPEM($pem)->publicKey(),
            default => throw new UnexpectedValueException('PEM type ' . $pem->type() . ' is not a valid public key.'),
        };
    }
}
