<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve25519;

use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\RFC8410PublicKey;
use UnexpectedValueException;
use function mb_strlen;

/**
 * Implements an intermediary object to store a public key using Curve25519.
 *
 * @see https://tools.ietf.org/html/rfc8410
 */
abstract class Curve25519PublicKey extends RFC8410PublicKey
{
    /**
     * @param string $publicKey Public key data
     */
    protected function __construct(string $publicKey)
    {
        if (mb_strlen($publicKey, '8bit') !== 32) {
            throw new UnexpectedValueException('Curve25519 public key must be exactly 32 bytes.');
        }
        parent::__construct($publicKey);
    }
}
