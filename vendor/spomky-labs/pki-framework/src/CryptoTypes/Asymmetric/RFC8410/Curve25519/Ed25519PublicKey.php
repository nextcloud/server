<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve25519;

use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\Ed25519AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;

/**
 * Implements an intermediary object to store Ed25519 public key.
 *
 * @see https://tools.ietf.org/html/rfc8410
 */
final class Ed25519PublicKey extends Curve25519PublicKey
{
    public static function create(string $publicKey): self
    {
        return new self($publicKey);
    }

    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return Ed25519AlgorithmIdentifier::create();
    }
}
