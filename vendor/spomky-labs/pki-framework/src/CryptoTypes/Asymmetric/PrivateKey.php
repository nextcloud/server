<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric;

use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\EC\ECPrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSAPrivateKey;
use UnexpectedValueException;

/**
 * Base class for private keys.
 */
abstract class PrivateKey
{
    /**
     * Get the private key algorithm identifier.
     */
    abstract public function algorithmIdentifier(): AlgorithmIdentifierType;

    /**
     * Get public key component of the asymmetric key pair.
     */
    abstract public function publicKey(): PublicKey;

    /**
     * Get DER encoding of the private key.
     */
    abstract public function toDER(): string;

    /**
     * Get the private key as a PEM.
     */
    abstract public function toPEM(): PEM;

    /**
     * Get the private key data in type specific encoding.
     */
    public function privateKeyData(): string
    {
        // By default encode in DER. This is the case with RSA and EC keys.
        // Other keys may have more specific encoding schemes, so this
        // method must be overridden by derived classes.
        return $this->toDER();
    }

    /**
     * Get the private key as a PrivateKeyInfo type.
     */
    public function privateKeyInfo(): PrivateKeyInfo
    {
        return PrivateKeyInfo::fromPrivateKey($this);
    }

    /**
     * Initialize private key from PEM.
     *
     * @return PrivateKey
     */
    public static function fromPEM(PEM $pem)
    {
        return match ($pem->type()) {
            PEM::TYPE_RSA_PRIVATE_KEY => RSAPrivateKey::fromDER($pem->data()),
            PEM::TYPE_EC_PRIVATE_KEY => ECPrivateKey::fromDER($pem->data()),
            PEM::TYPE_PRIVATE_KEY => PrivateKeyInfo::fromDER($pem->data())->privateKey(),
            default => throw new UnexpectedValueException('PEM type ' . $pem->type() . ' is not a valid private key.'),
        };
    }
}
