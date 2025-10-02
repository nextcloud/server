<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve25519;

use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\RFC8410PrivateKey;
use UnexpectedValueException;
use function mb_strlen;

/**
 * Implements an intermediary object to store a private key using Curve25519.
 *
 * @see https://tools.ietf.org/html/rfc8410
 */
abstract class Curve25519PrivateKey extends RFC8410PrivateKey
{
    /**
     * @param string $private_key Private key data
     * @param null|string $public_key Public key data
     */
    protected function __construct(string $private_key, ?string $public_key = null)
    {
        if (mb_strlen($private_key, '8bit') !== 32) {
            throw new UnexpectedValueException('Curve25519 private key must be exactly 32 bytes.');
        }
        if (isset($public_key) && mb_strlen($public_key, '8bit') !== 32) {
            throw new UnexpectedValueException('Curve25519 public key must be exactly 32 bytes.');
        }
        parent::__construct($private_key, $public_key);
    }
}
